<?php
/*
 * Sync hotel data into the Mini CRM (database crm_db on the same MySQL server).
 *   hotel guest (users)  ->  CRM customer   (matched by e-mail, so never duplicated)
 *   hotel booking        ->  CRM project     (one per booking, linked by projects.hotel_booking_id)
 *
 * The sync is idempotent (safe to run again) and never breaks the hotel: any failure is only logged.
 * Set CRM_SYNC to false to switch it off.
 */
if (!defined('CRM_SYNC'))    define('CRM_SYNC', true);
if (!defined('CRM_DB_NAME')) define('CRM_DB_NAME', 'crm_db');

function crm_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . CRM_DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Find or create the CRM customer for a hotel guest. Returns the CRM customer id. */
function crm_upsert_customer(string $name, string $email): int
{
    $crm = crm_db();
    $st = $crm->prepare('SELECT id FROM customers WHERE email = ? ORDER BY id LIMIT 1');
    $st->execute([$email]);
    if ($id = $st->fetchColumn()) {
        return (int)$id;
    }
    $crm->prepare('INSERT INTO customers (name, contact_person, email) VALUES (?,?,?)')->execute([$name, $name, $email]);
    return (int)$crm->lastInsertId();
}

/** Create or update the CRM customer for a hotel user id. */
function crm_sync_user(int $userId): void
{
    if (!CRM_SYNC) return;
    try {
        $st = db()->prepare('SELECT name, email FROM users WHERE id = ?');
        $st->execute([$userId]);
        if ($u = $st->fetch()) {
            crm_upsert_customer($u['name'], $u['email']);
        }
    } catch (Throwable $e) {
        error_log('CRM sync (user ' . $userId . '): ' . $e->getMessage());
    }
}

/** Create or update the CRM project for a hotel booking. Call after any change to the booking. */
function crm_sync_booking(int $bookingId): void
{
    if (!CRM_SYNC) return;
    try {
        $st = db()->prepare(
            'SELECT b.*, u.name AS guest_name, u.email AS guest_email, r.room_number, t.name AS room_type
               FROM bookings b
               JOIN users u ON u.id = b.user_id
               JOIN rooms r ON r.id = b.room_id
               JOIN room_types t ON t.id = r.room_type_id
              WHERE b.id = ?'
        );
        $st->execute([$bookingId]);
        $b = $st->fetch();
        if (!$b) return;

        $customerId = crm_upsert_customer($b['guest_name'], $b['guest_email']);
        $status     = $b['status'];
        $name       = sprintf('Hotel booking #%d: %s, %s to %s', $b['id'], $b['room_type'], $b['check_in'], $b['check_out']);
        if ($status === 'cancelled') $name .= ' (cancelled)';
        $desc = sprintf(
            "Room %s (%s)\nCheck-in %s, check-out %s\nGuests: %d adult(s), %d child(ren)\nStatus: %s\nNotes: %s",
            $b['room_number'], $b['room_type'], $b['check_in'], $b['check_out'],
            $b['adults'], $b['children'], $status, $b['notes'] ?: '-'
        );
        $income = $status === 'confirmed' ? (float)$b['total_price'] : 0.0;   // only confirmed stays count as income

        $crm = crm_db();
        $find = $crm->prepare('SELECT id FROM projects WHERE hotel_booking_id = ?');
        $find->execute([$bookingId]);
        if ($pid = $find->fetchColumn()) {
            $crm->prepare('UPDATE projects SET name = ?, description = ?, income = ?, customer_id = ? WHERE id = ?')
                ->execute([$name, $desc, $income, $customerId, $pid]);
        } else {
            $crm->prepare('INSERT INTO projects (name, description, income, expenditures, customer_id, hotel_booking_id, created_at) VALUES (?,?,?,0,?,?,?)')
                ->execute([$name, $desc, $income, $customerId, $bookingId, $b['created_at']]);
        }
    } catch (Throwable $e) {
        error_log('CRM sync (booking ' . $bookingId . '): ' . $e->getMessage());
    }
}
