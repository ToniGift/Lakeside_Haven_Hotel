<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at,
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings,
               (SELECT COALESCE(SUM(total_price),0) FROM bookings b WHERE b.user_id = u.id AND b.status = 'confirmed') AS spent
          FROM users u";
$params = [];
if ($q !== '') { $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ?'; $params = ["%$q%", "%$q%"]; }
$sql .= ' ORDER BY u.created_at DESC, u.id DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$users = $st->fetchAll();

$adminPage = 'users';
$pageTitle = 'Users';
include __DIR__ . '/_header.php';
?>
<h1 class="mb-3">Users</h1>
<form class="d-flex gap-2 mb-3" method="get">
  <input class="form-control" style="max-width:320px" name="q" value="<?= e($q) ?>" placeholder="Search name or email">
  <button class="btn btn-gold">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-link" href="<?= url('admin/users.php') ?>">Reset</a><?php endif; ?>
</form>
<div class="panel">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Bookings</th><th>Confirmed spend</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'dark' : 'light border' ?>"><?= e($u['role']) ?></span></td>
        <td><?= e(substr($u['created_at'], 0, 10)) ?></td>
        <td><?= (int)$u['bookings'] ?></td>
        <td><?= money($u['spent']) ?></td>
        <td class="text-end"><?php if ($u['bookings']): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/bookings.php?user=' . (int)$u['id']) ?>">Bookings</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="7" class="text-muted">No users found.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
