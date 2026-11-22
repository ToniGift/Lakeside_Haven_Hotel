<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

/* Quick actions from the list (POST + CSRF). */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'confirm') {
        $st = db()->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'");
        $st->execute([$id]);
        flash($st->rowCount() ? 'success' : 'danger', $st->rowCount() ? "Booking #$id confirmed." : 'Only pending bookings can be confirmed.');
    } elseif ($action === 'cancel') {
        $st = db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND status <> 'cancelled'");
        $st->execute([$id]);
        flash($st->rowCount() ? 'success' : 'danger', $st->rowCount() ? "Booking #$id cancelled." : 'Booking is already cancelled.');
    }
    $back = $_POST['back'] ?? '';
    redirect($back === 'view' ? 'admin/booking.php?id=' . $id : 'admin/bookings.php' . (!empty($_POST['qs']) ? '?' . $_POST['qs'] : ''));
}

$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$userId = (int)($_GET['user'] ?? 0);
$from   = trim($_GET['from'] ?? '');

$where = []; $params = [];
if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) { $where[] = 'b.status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($userId) { $where[] = 'b.user_id = ?'; $params[] = $userId; }
if (DateTime::createFromFormat('Y-m-d', $from)) { $where[] = 'b.check_in >= ?'; $params[] = $from; }

$sql = 'SELECT b.*, u.name AS guest, u.email, rt.name AS room_type, r.room_number
          FROM bookings b JOIN users u ON u.id = b.user_id
          JOIN rooms r ON r.id = b.room_id JOIN room_types rt ON rt.id = r.room_type_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY b.created_at DESC, b.id DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$counts = db()->query("SELECT status, COUNT(*) c FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$qs = $_SERVER['QUERY_STRING'] ?? '';

$adminPage = 'bookings';
$pageTitle = 'Bookings';
include __DIR__ . '/_header.php';
?>
<h1 class="mb-3">Bookings</h1>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm <?= $status === '' ? 'btn-gold' : 'btn-outline-secondary' ?>" href="<?= url('admin/bookings.php') ?>">All (<?= array_sum($counts) ?>)</a>
  <?php foreach (['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'] as $s => $c): ?>
    <a class="btn btn-sm <?= $status === $s ? 'btn-gold' : 'btn-outline-secondary' ?>" href="<?= url('admin/bookings.php?status=' . $s) ?>"><?= ucfirst($s) ?> (<?= (int)($counts[$s] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>

<form class="panel mb-3" method="get">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-md-5"><label class="small text-muted">Guest name or email</label><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>"></div>
    <div class="col-md-4"><label class="small text-muted">Check-in from</label><input type="date" class="form-control form-control-sm" name="from" value="<?= e($from) ?>"></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-gold">Filter</button><a class="btn btn-sm btn-link" href="<?= url('admin/bookings.php') ?>">Reset</a></div>
  </div>
</form>

<div class="panel">
  <?php if (!$rows): ?>
    <p class="text-muted mb-0">No bookings found.</p>
  <?php else: ?>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>#</th><th>Guest</th><th>Room</th><th>Dates</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $b):
        $badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'secondary'][$b['status']]; ?>
      <tr>
        <td><?= (int)$b['id'] ?></td>
        <td><?= e($b['guest']) ?><div class="small text-muted"><?= e($b['email']) ?></div></td>
        <td><?= e($b['room_type']) ?> <span class="text-muted">(<?= e($b['room_number']) ?>)</span></td>
        <td class="text-nowrap"><?= e($b['check_in']) ?> &rarr; <?= e($b['check_out']) ?><div class="small text-muted"><?= nights_between($b['check_in'], $b['check_out']) ?> night(s), <?= (int)$b['guests'] ?> guest(s)</div></td>
        <td><?= money($b['total_price']) ?></td>
        <td><span class="badge text-bg-<?= $badge ?>"><?= e($b['status']) ?></span></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/booking.php?id=' . (int)$b['id']) ?>">View</a>
          <?php if ($b['status'] === 'pending'): ?>
            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>"><button class="btn btn-sm btn-success">Confirm</button></form>
          <?php endif; ?>
          <?php if ($b['status'] !== 'cancelled'): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Cancel booking #<?= (int)$b['id'] ?>?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>"><button class="btn btn-sm btn-outline-danger">Cancel</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
