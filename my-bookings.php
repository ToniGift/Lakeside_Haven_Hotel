<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

/* Cancel: POST only, CSRF protected, own bookings only, and only before check-in. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    csrf_check();
    $st = db()->prepare("UPDATE bookings SET status = 'cancelled'
                          WHERE id = ? AND user_id = ? AND status <> 'cancelled' AND check_in > CURDATE()");
    $st->execute([(int)($_POST['id'] ?? 0), $user['id']]);
    flash($st->rowCount() ? 'success' : 'danger',
          $st->rowCount() ? 'Booking cancelled.' : 'This booking cannot be cancelled.');
    redirect('my-bookings.php');
}

$s = db()->prepare(
    'SELECT b.*, rt.name AS room_type FROM bookings b
       JOIN rooms r ON r.id = b.room_id JOIN room_types rt ON rt.id = r.room_type_id
      WHERE b.user_id = ? ORDER BY b.check_in DESC'
);
$s->execute([$user['id']]);
$all = $s->fetchAll();
$today = date('Y-m-d');
$upcoming = array_filter($all, fn($b) => $b['check_out'] >= $today && $b['status'] !== 'cancelled');
$past = array_filter($all, fn($b) => !in_array($b, $upcoming, true));

function booking_table(array $rows, string $today): void { ?>
  <div class="table-responsive"><table class="table table-stack align-middle bg-white rounded-3 shadow-sm">
    <thead><tr><th>#</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Guests</th><th>Total</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $b):
        $badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'][$b['status']]; ?>
      <tr>
        <td data-label="#"><?= (int)$b['id'] ?></td><td data-label="Room"><?= e($b['room_type']) ?></td>
        <td data-label="Check-in"><?= e($b['check_in']) ?></td><td data-label="Check-out"><?= e($b['check_out']) ?></td>
        <td data-label="Guests"><?= (int)$b['guests'] ?></td><td data-label="Total"><?= money($b['total_price']) ?></td>
        <td data-label="Status"><span class="badge text-bg-<?= $badge ?>"><?= e($b['status']) ?></span></td>
        <td class="text-end actions">
          <a class="btn btn-sm btn-outline-gold" href="<?= url('booking.php?id=' . (int)$b['id']) ?>">View</a>
          <?php if ($b['status'] !== 'cancelled' && $b['check_in'] > $today): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Cancel this booking?')">
              <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Cancel</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php }

$pageTitle = 'My bookings';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h1 class="section-title mb-4">My bookings</h1>
  <?php if (!$all): ?>
    <div class="alert alert-info">You have no bookings yet. <a href="<?= url('rooms.php') ?>">Browse rooms</a></div>
  <?php endif; ?>
  <?php if ($upcoming): ?><h5>Upcoming</h5><?php booking_table($upcoming, $today); endif; ?>
  <?php if ($past): ?><h5 class="mt-4">Past and cancelled</h5><?php booking_table($past, $today); endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
