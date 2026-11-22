<?php
require_once __DIR__ . '/../includes/auth.php';
$adminPage = 'dashboard';
$pageTitle = 'Dashboard';
include __DIR__ . '/_header.php';

$one = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$stats = [
    'Room types'          => $one("SELECT COUNT(*) FROM room_types"),
    'Rooms / beds'        => $one("SELECT COUNT(*) FROM rooms"),
    'Active bookings'     => $one("SELECT COUNT(*) FROM bookings WHERE status <> 'cancelled' AND check_out >= CURDATE()"),
    'Pending to confirm'  => $one("SELECT COUNT(*) FROM bookings WHERE status = 'pending'"),
    'Registered users'    => $one("SELECT COUNT(*) FROM users WHERE role = 'user'"),
    'Messages'            => $one("SELECT COUNT(*) FROM messages"),
];
$revenue = (float)db()->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

$upcoming = db()->query(
    "SELECT b.id, b.check_in, b.check_out, b.status, u.name AS guest, rt.name AS room_type, r.room_number
       FROM bookings b JOIN users u ON u.id = b.user_id
       JOIN rooms r ON r.id = b.room_id JOIN room_types rt ON rt.id = r.room_type_id
      WHERE b.status <> 'cancelled' AND b.check_in >= CURDATE()
      ORDER BY b.check_in LIMIT 8"
)->fetchAll();
?>
<h1 class="mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $n): ?>
    <div class="col-6 col-lg-4 col-xl-2"><div class="stat-card"><div class="num"><?= $n ?></div><div class="small text-muted"><?= e($label) ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="stat-card mb-4 d-inline-block"><div class="num"><?= money($revenue) ?></div><div class="small text-muted">Confirmed revenue</div></div>

<div class="panel">
  <h5 class="mb-3">Upcoming check-ins</h5>
  <?php if (!$upcoming): ?>
    <p class="text-muted mb-0">No upcoming bookings yet.</p>
  <?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $b): ?>
        <tr>
          <td><?= e($b['guest']) ?></td><td><?= e($b['room_type']) ?> (<?= e($b['room_number']) ?>)</td>
          <td><?= e($b['check_in']) ?></td><td><?= e($b['check_out']) ?></td>
          <td><span class="badge text-bg-<?= $b['status'] === 'confirmed' ? 'success' : 'warning' ?>"><?= e($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
