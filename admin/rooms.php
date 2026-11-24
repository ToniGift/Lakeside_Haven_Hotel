<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

/* Delete (POST only, CSRF protected). Refuse if the room type has bookings. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    $cnt = db()->prepare('SELECT COUNT(*) FROM bookings b JOIN rooms r ON r.id = b.room_id WHERE r.room_type_id = ?');
    $cnt->execute([$id]);
    if ((int)$cnt->fetchColumn() > 0) {
        flash('danger', 'This room type has bookings and cannot be deleted. Deactivate it instead.');
        redirect('admin/rooms.php');
    }

    $imgs = db()->prepare('SELECT filename FROM room_images WHERE room_type_id = ?');
    $imgs->execute([$id]);
    foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $f) {
        @unlink(UPLOAD_DIR . basename($f));
    }
    db()->prepare('DELETE FROM room_types WHERE id = ?')->execute([$id]);
    flash('success', 'Room type deleted.');
    redirect('admin/rooms.php');
}

$adminPage = 'rooms';
$pageTitle = 'Rooms';
$types = db()->query(
    "SELECT rt.*, (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id) AS room_count,
            (SELECT filename FROM room_images ri WHERE ri.room_type_id = rt.id ORDER BY ri.is_cover DESC, ri.sort_order LIMIT 1) AS cover
       FROM room_types rt ORDER BY rt.id"
)->fetchAll();
include __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="mb-0">Rooms</h1>
  <a href="<?= url('admin/room-form.php') ?>" class="btn btn-gold">+ Add room type</a>
</div>
<div class="panel">
  <div class="table-responsive"><table class="table table-stack align-middle mb-0">
    <thead><tr><th></th><th>Name</th><th>Price / night</th><th>Capacity</th><th>Rooms</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t): ?>
      <tr>
        <td class="photo-cell"><?= $t['cover'] ? '<img class="thumb" src="' . e(UPLOAD_URL . $t['cover']) . '" alt="">' : '<div class="thumb room-img placeholder-img" style="height:80px;font-size:.7rem">No photo</div>' ?></td>
        <td data-label="Name"><strong><?= e($t['name']) ?></strong><div class="small text-muted"><?= e($t['beds']) ?></div></td>
        <td data-label="Price / night"><?= money($t['price_per_night']) ?></td>
        <td data-label="Capacity"><?= (int)$t['capacity'] ?></td>
        <td data-label="Rooms"><?= (int)$t['room_count'] ?></td>
        <td data-label="Status"><span class="badge text-bg-<?= $t['is_active'] ? 'success' : 'secondary' ?>"><?= $t['is_active'] ? 'Active' : 'Hidden' ?></span></td>
        <td class="text-end actions">
          <a class="btn btn-sm btn-outline-gold" href="<?= url('room.php?slug=' . urlencode($t['slug'])) ?>" target="_blank">View</a>
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/room-form.php?id=' . (int)$t['id']) ?>">Edit</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this room type and all its photos?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
