<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare(
    'SELECT b.*, u.name AS guest, u.email, u.created_at AS member_since, rt.name AS room_type, rt.price_per_night, r.room_number
       FROM bookings b JOIN users u ON u.id = b.user_id
       JOIN rooms r ON r.id = b.room_id JOIN room_types rt ON rt.id = r.room_type_id
      WHERE b.id = ?'
);
$s->execute([$id]);
$b = $s->fetch();
if (!$b) {
    flash('danger', 'Booking not found.');
    redirect('admin/bookings.php');
}

$badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'][$b['status']];
$adminPage = 'bookings';
$pageTitle = 'Booking #' . $b['id'];
include __DIR__ . '/_header.php';
?>
<a href="<?= url('admin/bookings.php') ?>" class="small">&larr; All bookings</a>
<h1 class="mt-2 mb-1">Booking #<?= (int)$b['id'] ?></h1>
<p><span class="badge text-bg-<?= $badge ?> fs-6"><?= e(ucfirst($b['status'])) ?></span></p>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel">
      <table class="table table-borderless mb-0">
        <tr><td class="text-muted">Room</td><td class="text-end"><?= e($b['room_type']) ?> (<?= e($b['room_number']) ?>)</td></tr>
        <tr><td class="text-muted">Check-in</td><td class="text-end"><?= e($b['check_in']) ?></td></tr>
        <tr><td class="text-muted">Check-out</td><td class="text-end"><?= e($b['check_out']) ?></td></tr>
        <tr><td class="text-muted">Nights</td><td class="text-end"><?= nights_between($b['check_in'], $b['check_out']) ?></td></tr>
        <tr><td class="text-muted">Guests</td><td class="text-end"><?= e(party_label((int)$b['adults'], (int)$b['children'])) ?></td></tr>
        <tr><td class="text-muted">Rate</td><td class="text-end"><?= money($b['price_per_night']) ?> / night</td></tr>
        <tr class="fw-bold"><td>Total</td><td class="text-end"><?= money($b['total_price']) ?></td></tr>
        <tr><td class="text-muted">Booked on</td><td class="text-end"><?= e($b['created_at']) ?></td></tr>
        <tr><td class="text-muted">Special requests</td><td class="text-end"><?= $b['notes'] ? e($b['notes']) : '<span class="text-muted">none</span>' ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel mb-3">
      <h5>Guest</h5>
      <p class="mb-1"><strong><?= e($b['guest']) ?></strong></p>
      <p class="mb-1"><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></p>
      <p class="small text-muted mb-2">Member since <?= e(substr($b['member_since'], 0, 10)) ?></p>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/bookings.php?user=' . (int)$b['user_id']) ?>">All bookings by this guest</a>
    </div>
    <div class="panel d-flex gap-2">
      <?php if ($b['status'] === 'pending'): ?>
        <form method="post" action="<?= url('admin/bookings.php') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="back" value="view"><button class="btn btn-success">Confirm booking</button></form>
      <?php endif; ?>
      <?php if ($b['status'] !== 'cancelled'): ?>
        <form method="post" action="<?= url('admin/bookings.php') ?>" onsubmit="return confirm('Cancel this booking?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="back" value="view"><button class="btn btn-outline-danger">Cancel booking</button></form>
      <?php else: ?>
        <span class="text-muted">This booking is cancelled.</span>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
