<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie: not readable by JavaScript, not sent cross-site.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Basic security headers on every web response.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/** Escape output (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function money($amount): string
{
    return CURRENCY . number_format((float)$amount, 2);
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Invalid or expired form. Go back, refresh and try again.');
    }
}

/* ---------- Misc helpers ---------- */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function nights_between(string $in, string $out): int
{
    return (int)(new DateTime($in))->diff(new DateTime($out))->days;
}

/* ---------- Room availability ---------- */

/** Validate a check-in / check-out pair. Returns an error message or null. */
function validate_dates(string $in, string $out): ?string
{
    $a = DateTime::createFromFormat('Y-m-d', $in);
    $b = DateTime::createFromFormat('Y-m-d', $out);
    if (!$a || $a->format('Y-m-d') !== $in || !$b || $b->format('Y-m-d') !== $out) {
        return 'Please choose valid dates.';
    }
    if ($a < new DateTime('today')) {
        return 'Check-in cannot be in the past.';
    }
    if ($b <= $a) {
        return 'Check-out must be after check-in.';
    }
    return null;
}

/**
 * Active rooms of a type that have no non-cancelled booking overlapping [in, out).
 * Overlap rule: existing.check_in < new.check_out AND existing.check_out > new.check_in
 */
function available_rooms(int $roomTypeId, string $in, string $out): array
{
    $stmt = db()->prepare(
        "SELECT r.id, r.room_number FROM rooms r
          WHERE r.room_type_id = ? AND r.status = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM bookings b
                 WHERE b.room_id = r.id AND b.status <> 'cancelled'
                   AND b.check_in < ? AND b.check_out > ?)
          ORDER BY r.room_number"
    );
    $stmt->execute([$roomTypeId, $out, $in]);
    return $stmt->fetchAll();
}

/** Best active offer discount (percent) for a date range; 0 if none. */
function current_discount(): int
{
    $row = db()->query(
        "SELECT MAX(discount_percent) d FROM offers WHERE is_active = 1
           AND (valid_from IS NULL OR valid_from <= CURDATE())
           AND (valid_to IS NULL OR valid_to >= CURDATE())"
    )->fetch();
    return (int)($row['d'] ?? 0);
}

function room_cover_html(?string $file, string $name, string $extraClass = ''): string
{
    if ($file) {
        return '<img class="room-img ' . e($extraClass) . '" src="' . e(UPLOAD_URL . $file) . '" alt="' . e($name) . '">';
    }
    return '<div class="room-img placeholder-img ' . e($extraClass) . '">' . e($name) . '</div>';
}

/* ---------- Booking ---------- */

/** Price for a stay after the best active offer. */
function stay_price(float $perNight, string $in, string $out): float
{
    $subtotal = $perNight * nights_between($in, $out);
    return round($subtotal * (100 - current_discount()) / 100, 2);
}

/**
 * Create a booking inside a transaction so two people can never get the same room.
 * Room rows of the type are locked (FOR UPDATE) while availability is re-checked.
 * Returns ['ok' => true, 'id' => int] or ['ok' => false, 'error' => string].
 */
function create_booking(int $userId, int $roomTypeId, string $in, string $out, int $adults, int $children, string $notes = ''): array
{
    if ($err = validate_dates($in, $out)) {
        return ['ok' => false, 'error' => $err];
    }
    $pdo = db();
    $t = $pdo->prepare('SELECT id, capacity, price_per_night FROM room_types WHERE id = ? AND is_active = 1');
    $t->execute([$roomTypeId]);
    $type = $t->fetch();
    if (!$type) {
        return ['ok' => false, 'error' => 'This room is not available for booking.'];
    }
    if ($adults < 1) {
        return ['ok' => false, 'error' => 'At least one adult is needed for a booking.'];
    }
    if ($children < 0 || $children > 20) {
        return ['ok' => false, 'error' => 'Please check the number of children.'];
    }
    $guests = $adults + $children;                       // total people in the room
    if ($guests > (int)$type['capacity']) {
        return ['ok' => false, 'error' => 'This room sleeps at most ' . (int)$type['capacity'] . ' people, adults and children together.'];
    }

    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT id FROM rooms WHERE room_type_id = ? AND status = 'active' ORDER BY room_number FOR UPDATE");
        $lock->execute([$roomTypeId]);
        $roomIds = $lock->fetchAll(PDO::FETCH_COLUMN);

        $free = null;
        $chk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status <> 'cancelled' AND check_in < ? AND check_out > ?");
        foreach ($roomIds as $rid) {
            $chk->execute([$rid, $out, $in]);
            if ((int)$chk->fetchColumn() === 0) { $free = (int)$rid; break; }
        }
        if ($free === null) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Sorry, this room was just booked for those dates. Please try other dates.'];
        }

        $price = stay_price((float)$type['price_per_night'], $in, $out);
        $pdo->prepare('INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, adults, children, total_price, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$userId, $free, $in, $out, $guests, $adults, $children, $price, 'pending', $notes !== '' ? $notes : null]);
        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return ['ok' => true, 'id' => $id];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'error' => 'Could not complete the booking. Please try again.'];
    }
}

/* ---------- Room photos with indicator dots ---------- */

/** Photo file names per room type id, cover first. */
function room_photos_map(array $typeIds): array
{
    $typeIds = array_values(array_map('intval', $typeIds));
    if (!$typeIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($typeIds), '?'));
    $st = db()->prepare("SELECT room_type_id, filename FROM room_images WHERE room_type_id IN ($in)
                         ORDER BY room_type_id, is_cover DESC, sort_order, id");
    $st->execute($typeIds);
    $map = [];
    foreach ($st->fetchAll() as $r) {
        $map[(int)$r['room_type_id']][] = $r['filename'];
    }
    return $map;
}

/**
 * Room photo for a card. One photo = plain image. Several photos = a small slideshow with
 * one dot per photo (so the dots show how many photos the room has) and swipe / arrows.
 */
function room_media_html(string $name, array $photos, string $id): string
{
    if (!$photos) {
        return room_cover_html(null, $name);
    }
    if (count($photos) === 1) {
        return room_cover_html($photos[0], $name);
    }
    $n = count($photos);
    $h = '<div id="' . e($id) . '" class="carousel slide room-media" data-bs-ride="false" data-bs-touch="true" aria-label="' . e($name) . ' photos">';
    $h .= '<div class="carousel-indicators">';
    foreach ($photos as $i => $f) {
        $h .= '<button type="button" data-bs-target="#' . e($id) . '" data-bs-slide-to="' . $i . '"'
            . ($i === 0 ? ' class="active" aria-current="true"' : '') . ' aria-label="Photo ' . ($i + 1) . ' of ' . $n . '"></button>';
    }
    $h .= '</div><div class="carousel-inner">';
    foreach ($photos as $i => $f) {
        $h .= '<div class="carousel-item' . ($i === 0 ? ' active' : '') . '"><img class="room-img" src="' . e(UPLOAD_URL . $f)
            . '" alt="' . e($name) . ' photo ' . ($i + 1) . ' of ' . $n . '"' . ($i > 0 ? ' loading="lazy"' : '') . '></div>';
    }
    $h .= '</div>';
    foreach (['prev' => 'Previous', 'next' => 'Next'] as $dir => $label) {
        $h .= '<button class="carousel-control-' . $dir . '" type="button" data-bs-target="#' . e($id) . '" data-bs-slide="' . $dir . '">'
            . '<span class="carousel-control-' . $dir . '-icon" aria-hidden="true"></span><span class="visually-hidden">' . $label . '</span></button>';
    }
    return $h . '</div>';
}

/* ---------- Adults and children ---------- */

/** Largest number of people any active room type sleeps. */
function max_capacity(): int
{
    return max(1, (int)db()->query('SELECT MAX(capacity) FROM room_types WHERE is_active = 1')->fetchColumn());
}

/** Adults and children from the request, kept inside what a room can hold. The old "guests" value counts as adults. */
function read_party(int $maxTotal): array
{
    $adults   = (int)($_REQUEST['adults'] ?? ($_REQUEST['guests'] ?? 1));
    $children = (int)($_REQUEST['children'] ?? 0);
    $adults   = max(1, min($maxTotal, $adults));
    $children = max(0, min($maxTotal - $adults, $children));
    return [$adults, $children];
}

/** "2 adults, 1 child" */
function party_label(int $adults, int $children): string
{
    $s = $adults . ' adult' . ($adults === 1 ? '' : 's');
    if ($children > 0) {
        $s .= ', ' . $children . ' child' . ($children === 1 ? '' : 'ren');
    }
    return $s;
}

/** Label + dropdown for adults (1 or more) or children (0 or more). main.js keeps adults + children within $max. */
function party_select(string $kind, int $selected, int $max): string
{
    $adults = $kind === 'adults';
    $from = $adults ? 1 : 0;
    $to   = $adults ? $max : max(0, $max - 1);
    $h  = '<label for="' . $kind . '">' . ($adults ? 'Adults' : 'Children') . '</label>';
    $h .= '<select id="' . $kind . '" name="' . $kind . '" class="form-select party-select" data-max-total="' . $max . '">';
    for ($i = $from; $i <= $to; $i++) {
        $h .= '<option value="' . $i . '"' . ($i === $selected ? ' selected' : '') . '>' . $i . '</option>';
    }
    return $h . '</select>';
}
