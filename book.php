<?php
require_once __DIR__ . '/includes/auth.php';
require_login();                       // visitors are sent to the login page; only registered users can book

if (is_admin()) {
    flash('warning', 'Admins manage bookings in the admin area.');
    redirect('admin/bookings.php');
}
$user = current_user();

$typeId = (int)($_REQUEST['room_type'] ?? 0);
$in     = trim($_REQUEST['check_in'] ?? '');
$out    = trim($_REQUEST['check_out'] ?? '');
$adults   = (int)($_REQUEST['adults'] ?? ($_REQUEST['guests'] ?? 1));
$children = (int)($_REQUEST['children'] ?? 0);
$guests   = $adults + $children;               // total people

$s = db()->prepare('SELECT * FROM room_types WHERE id = ? AND is_active = 1');
$s->execute([$typeId]);
$room = $s->fetch();
if (!$room) {
    flash('danger', 'Room not found.');
    redirect('rooms.php');
}

$dateError = validate_dates($in, $out);
if ($dateError || $adults < 1 || $children < 0 || $guests > (int)$room['capacity']) {
    flash('warning', $dateError ?: 'Please choose the number of adults and children (at least one adult), then your dates.');
    redirect('room.php?slug=' . urlencode($room['slug']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $notes = mb_substr(trim($_POST['notes'] ?? ''), 0, 1000);
    $res = create_booking((int)$user['id'], $typeId, $in, $out, $adults, $children, $notes);
    if ($res['ok']) {
        flash('success', 'Your booking request has been received.');
        redirect('booking.php?id=' . $res['id']);
    }
    flash('danger', $res['error']);
    redirect('room.php?slug=' . urlencode($room['slug']) . '&check_in=' . urlencode($in) . '&check_out=' . urlencode($out) . '&adults=' . $adults . '&children=' . $children);
}

$nights   = nights_between($in, $out);
$subtotal = $nights * (float)$room['price_per_night'];
$total    = stay_price((float)$room['price_per_night'], $in, $out);
$available = count(available_rooms($typeId, $in, $out));

$pageTitle = 'Confirm booking';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <h1 class="section-title mb-3">Confirm your booking</h1>
      <?php if ($available === 0): ?>
        <div class="alert alert-secondary">Sorry, no rooms of this type are available for those dates.</div>
        <a class="btn btn-gold" href="<?= url('room.php?slug=' . urlencode($room['slug'])) ?>">Choose other dates</a>
      <?php else: ?>
      <div class="panel bg-white rounded-3 p-4 shadow-sm mb-3">
        <h4><?= e($room['name']) ?></h4>
        <table class="table table-borderless mb-0">
          <tr><td class="text-muted">Guest</td><td class="text-end"><?= e($user['name']) ?> (<?= e($user['email']) ?>)</td></tr>
          <tr><td class="text-muted">Check-in</td><td class="text-end"><?= e($in) ?></td></tr>
          <tr><td class="text-muted">Check-out</td><td class="text-end"><?= e($out) ?></td></tr>
          <tr><td class="text-muted">Guests</td><td class="text-end"><?= e(party_label($adults, $children)) ?></td></tr>
          <tr><td class="text-muted"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?> &times; <?= money($room['price_per_night']) ?></td><td class="text-end"><?= money($subtotal) ?></td></tr>
          <?php if ($total < $subtotal): ?>
            <tr class="text-success"><td>Offer discount</td><td class="text-end">-<?= money($subtotal - $total) ?></td></tr>
          <?php endif; ?>
          <tr class="fw-bold fs-5"><td>Total</td><td class="text-end"><?= money($total) ?></td></tr>
        </table>
      </div>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="room_type" value="<?= (int)$typeId ?>">
        <input type="hidden" name="check_in" value="<?= e($in) ?>">
        <input type="hidden" name="check_out" value="<?= e($out) ?>">
        <input type="hidden" name="adults" value="<?= $adults ?>">
        <input type="hidden" name="children" value="<?= $children ?>">
        <div class="mb-3">
          <label class="form-label" for="notes">Special requests (optional)</label>
          <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="1000"></textarea>
        </div>
        <p class="small text-muted">Payment is made at the hotel. The hotel will confirm your booking.</p>
        <button class="btn btn-gold btn-lg">Confirm booking</button>
        <a class="btn btn-link" href="<?= url('room.php?slug=' . urlencode($room['slug'])) ?>">Cancel</a>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
