<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
