<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

// A user can only ever open their own booking.
$s = db()->prepare(
    'SELECT b.*, rt.name AS room_type, rt.slug, r.room_number
       FROM bookings b JOIN rooms r ON r.id = b.room_id JOIN room_types rt ON rt.id = r.room_type_id
      WHERE b.id = ? AND b.user_id = ?'
);
$s->execute([(int)($_GET['id'] ?? 0), $user['id']]);
$b = $s->fetch();
if (!$b) {
    http_response_code(404);
    flash('danger', 'Booking not found.');
    redirect('my-bookings.php');
}

$pageTitle = 'Booking #' . $b['id'];
$badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'][$b['status']];
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="row justify-content-center"><div class="col-lg-7">
    <h1 class="section-title mb-1">Booking #<?= (int)$b['id'] ?></h1>
    <p><span class="badge text-bg-<?= $badge ?> fs-6"><?= e(ucfirst($b['status'])) ?></span></p>
    <div class="bg-white rounded-3 shadow-sm p-4 mb-3">
      <h4><?= e($b['room_type']) ?></h4>
      <table class="table table-borderless mb-0">
        <tr><td class="text-muted">Check-in</td><td class="text-end"><?= e($b['check_in']) ?></td></tr>
        <tr><td class="text-muted">Check-out</td><td class="text-end"><?= e($b['check_out']) ?></td></tr>
        <tr><td class="text-muted">Guests</td><td class="text-end"><?= e(party_label((int)$b['adults'], (int)$b['children'])) ?></td></tr>
        <tr><td class="text-muted">Booked on</td><td class="text-end"><?= e($b['created_at']) ?></td></tr>
        <?php if ($b['notes']): ?><tr><td class="text-muted">Requests</td><td class="text-end"><?= e($b['notes']) ?></td></tr><?php endif; ?>
        <tr class="fw-bold fs-5"><td>Total (pay at the hotel)</td><td class="text-end"><?= money($b['total_price']) ?></td></tr>
      </table>
    </div>
    <?php if ($b['status'] === 'pending'): ?>
      <p class="text-muted">The hotel will confirm your booking shortly.</p>
    <?php endif; ?>
    <a class="btn btn-gold" href="<?= url('my-bookings.php') ?>">My bookings</a>
    <button class="btn btn-outline-gold" onclick="window.print()">Print</button>
  </div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
