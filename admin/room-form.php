<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

const MAX_UPLOAD_BYTES = 4 * 1024 * 1024;
const ALLOWED_IMAGES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

$id = (int)($_GET['id'] ?? 0);
$room = null;
if ($id) {
    $s = db()->prepare('SELECT * FROM room_types WHERE id = ?');
    $s->execute([$id]);
    $room = $s->fetch();
    if (!$room) {
        flash('danger', 'Room type not found.');
        redirect('admin/rooms.php');
    }
}

$errors = [];
$old = $room ?: ['name' => '', 'description' => '', 'price_per_night' => '', 'capacity' => 2, 'beds' => '', 'size_m2' => '', 'is_active' => 1];
$selectedAmenities = [];
if ($id) {
    $s = db()->prepare('SELECT amenity_id FROM room_type_amenities WHERE room_type_id = ?');
    $s->execute([$id]);
    $selectedAmenities = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}

function unique_slug(string $name, int $ignoreId): string
{
    $base = slugify($name) ?: 'room';
    $slug = $base;
    $n = 2;
    $q = db()->prepare('SELECT COUNT(*) FROM room_types WHERE slug = ? AND id <> ?');
    while (true) {
        $q->execute([$slug, $ignoreId]);
        if ((int)$q->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $n++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';

    /* ---- save room type ---- */
    if ($action === 'save') {
        $old = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price_per_night' => trim($_POST['price_per_night'] ?? ''),
            'capacity' => (int)($_POST['capacity'] ?? 0),
            'beds' => trim($_POST['beds'] ?? ''),
            'size_m2' => trim($_POST['size_m2'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $selectedAmenities = array_map('intval', $_POST['amenities'] ?? []);

        if ($old['name'] === '' || mb_strlen($old['name']) > 100) $errors[] = 'Name is required (max 100 characters).';
        if (!is_numeric($old['price_per_night']) || $old['price_per_night'] < 0 || $old['price_per_night'] > 99999) $errors[] = 'Enter a valid price.';
        if ($old['capacity'] < 1 || $old['capacity'] > 20) $errors[] = 'Capacity must be between 1 and 20.';
        if ($old['size_m2'] !== '' && (!ctype_digit($old['size_m2']) || (int)$old['size_m2'] > 5000)) $errors[] = 'Size must be a whole number.';

        if (!$errors) {
            $params = [$old['name'], unique_slug($old['name'], $id), $old['description'], $old['price_per_night'],
                       $old['capacity'], $old['beds'] ?: null, $old['size_m2'] === '' ? null : (int)$old['size_m2'], $old['is_active']];
            if ($id) {
                db()->prepare('UPDATE room_types SET name=?, slug=?, description=?, price_per_night=?, capacity=?, beds=?, size_m2=?, is_active=? WHERE id=?')
                    ->execute([...$params, $id]);
            } else {
                db()->prepare('INSERT INTO room_types (name, slug, description, price_per_night, capacity, beds, size_m2, is_active) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute($params);
                $id = (int)db()->lastInsertId();
            }
            db()->prepare('DELETE FROM room_type_amenities WHERE room_type_id = ?')->execute([$id]);
            $ins = db()->prepare('INSERT IGNORE INTO room_type_amenities (room_type_id, amenity_id) SELECT ?, id FROM amenities WHERE id = ?');
            foreach (array_unique($selectedAmenities) as $aid) $ins->execute([$id, $aid]);

            flash('success', 'Room type saved.');
            redirect('admin/room-form.php?id=' . $id);
        }
    }

    /* ---- actions below only make sense on an existing room type ---- */
    elseif ($id) {
        if ($action === 'add_room') {
            $num = trim($_POST['room_number'] ?? '');
            if ($num === '' || mb_strlen($num) > 10) {
                flash('danger', 'Enter a room number (max 10 characters).');
            } else {
                try {
                    db()->prepare('INSERT INTO rooms (room_type_id, room_number) VALUES (?, ?)')->execute([$id, $num]);
                    flash('success', "Room $num added.");
                } catch (PDOException $e) {
                    flash('danger', "Room number $num already exists.");
                }
            }
        } elseif ($action === 'toggle_room') {
            db()->prepare("UPDATE rooms SET status = IF(status='active','maintenance','active') WHERE id = ? AND room_type_id = ?")
                ->execute([(int)$_POST['room_id'], $id]);
            flash('success', 'Room status updated.');
        } elseif ($action === 'remove_room') {
            $c = db()->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = ?');
            $c->execute([(int)$_POST['room_id']]);
            if ((int)$c->fetchColumn() > 0) {
                flash('danger', 'This room has bookings. Set it to maintenance instead of deleting it.');
            } else {
                db()->prepare('DELETE FROM rooms WHERE id = ? AND room_type_id = ?')->execute([(int)$_POST['room_id'], $id]);
                flash('success', 'Room removed.');
            }
        } elseif ($action === 'upload') {
            $files = $_FILES['images'] ?? null;
            $saved = 0;
            if ($files && is_array($files['name'])) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                foreach ($files['name'] as $i => $origName) {
                    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) { flash('danger', "Upload failed for $origName."); continue; }
                    if ($files['size'][$i] > MAX_UPLOAD_BYTES) { flash('danger', "$origName is larger than 4 MB."); continue; }
                    $mime = $finfo->file($files['tmp_name'][$i]);
                    if (!isset(ALLOWED_IMAGES[$mime]) || !@getimagesize($files['tmp_name'][$i])) {
                        flash('danger', "$origName is not a JPG, PNG or WebP image."); continue;
                    }
                    $name = bin2hex(random_bytes(12)) . '.' . ALLOWED_IMAGES[$mime];
                    if (!move_uploaded_file($files['tmp_name'][$i], UPLOAD_DIR . $name)) { flash('danger', "Could not save $origName."); continue; }
                    $hasCover = (int)db()->query('SELECT COUNT(*) FROM room_images WHERE is_cover = 1 AND room_type_id = ' . $id)->fetchColumn();
                    db()->prepare('INSERT INTO room_images (room_type_id, filename, is_cover, sort_order) VALUES (?, ?, ?, ?)')
                        ->execute([$id, $name, $hasCover ? 0 : 1, 0]);
                    $saved++;
                }
            }
            if ($saved) flash('success', "$saved photo(s) uploaded.");
        } elseif ($action === 'cover') {
            db()->prepare('UPDATE room_images SET is_cover = 0 WHERE room_type_id = ?')->execute([$id]);
            db()->prepare('UPDATE room_images SET is_cover = 1 WHERE id = ? AND room_type_id = ?')->execute([(int)$_POST['image_id'], $id]);
            flash('success', 'Cover photo updated.');
        } elseif ($action === 'delete_image') {
            $s = db()->prepare('SELECT filename, is_cover FROM room_images WHERE id = ? AND room_type_id = ?');
            $s->execute([(int)$_POST['image_id'], $id]);
            if ($img = $s->fetch()) {
                db()->prepare('DELETE FROM room_images WHERE id = ?')->execute([(int)$_POST['image_id']]);
                // A photo can be shared by several rooms: delete the file only when nobody uses it any more.
                $used = db()->prepare('SELECT COUNT(*) FROM room_images WHERE filename = ?');
                $used->execute([$img['filename']]);
                if ((int)$used->fetchColumn() === 0) {
                    @unlink(UPLOAD_DIR . basename($img['filename']));
                }
                if ($img['is_cover']) {
                    db()->prepare('UPDATE room_images SET is_cover = 1 WHERE room_type_id = ? ORDER BY id LIMIT 1')->execute([$id]);
                }
                flash('success', 'Photo deleted.');
            }
        }
        redirect('admin/room-form.php?id=' . $id);
    }
}

$allAmenities = db()->query('SELECT id, name FROM amenities ORDER BY name')->fetchAll();
$images = $rooms = [];
if ($id) {
    $s = db()->prepare('SELECT * FROM room_images WHERE room_type_id = ? ORDER BY is_cover DESC, id');
    $s->execute([$id]);
    $images = $s->fetchAll();
    $s = db()->prepare('SELECT * FROM rooms WHERE room_type_id = ? ORDER BY room_number');
    $s->execute([$id]);
    $rooms = $s->fetchAll();
}

$adminPage = 'rooms';
$pageTitle = $id ? 'Edit room type' : 'New room type';
include __DIR__ . '/_header.php';
?>
<a href="<?= url('admin/rooms.php') ?>" class="small">&larr; All rooms</a>
<h1 class="mb-4 mt-2"><?= $id ? 'Edit: ' . e($room['name']) : 'New room type' ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-7">
    <form method="post" class="panel">
      <?= csrf_field() ?><input type="hidden" name="action" value="save">
      <div class="row g-3">
        <div class="col-12"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($old['name']) ?>" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?= e($old['description']) ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Price / night (<?= CURRENCY ?>)</label><input class="form-control" type="number" step="0.01" min="0" name="price_per_night" value="<?= e($old['price_per_night']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Capacity</label><input class="form-control" type="number" min="1" max="20" name="capacity" value="<?= e($old['capacity']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Size (m&sup2;)</label><input class="form-control" type="number" min="0" name="size_m2" value="<?= e($old['size_m2']) ?>"></div>
        <div class="col-12"><label class="form-label">Beds</label><input class="form-control" name="beds" value="<?= e($old['beds']) ?>" placeholder="e.g. 1 double bed"></div>
        <div class="col-12">
          <label class="form-label d-block">Amenities</label>
          <?php foreach ($allAmenities as $a): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="amenities[]" id="am<?= (int)$a['id'] ?>" value="<?= (int)$a['id'] ?>" <?= in_array((int)$a['id'], $selectedAmenities, true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="am<?= (int)$a['id'] ?>"><?= e($a['name']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="col-12 form-check ms-2">
          <input class="form-check-input" type="checkbox" name="is_active" id="act" <?= $old['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="act">Visible on the website</label>
        </div>
        <div class="col-12"><button class="btn btn-gold"><?= $id ? 'Save changes' : 'Create room type' ?></button></div>
      </div>
    </form>
  </div>

  <?php if ($id): ?>
  <div class="col-xl-5">
    <div class="panel mb-4">
      <h5>Rooms / beds</h5>
      <table class="table table-sm align-middle">
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td><strong><?= e($r['room_number']) ?></strong></td>
            <td><span class="badge text-bg-<?= $r['status'] === 'active' ? 'success' : 'warning' ?>"><?= e($r['status']) ?></span></td>
            <td class="text-end text-nowrap">
              <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_room"><input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-secondary">Toggle</button></form>
              <form method="post" class="d-inline" onsubmit="return confirm('Remove this room?')"><?= csrf_field() ?><input type="hidden" name="action" value="remove_room"><input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
      <form method="post" class="d-flex gap-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_room">
        <input class="form-control form-control-sm" name="room_number" placeholder="New room number" maxlength="10" required>
        <button class="btn btn-sm btn-gold text-nowrap">Add room</button>
      </form>
    </div>

    <div class="panel">
      <h5>Photos</h5>
      <form method="post" enctype="multipart/form-data" class="mb-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <input class="form-control mb-2" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <div class="form-text mb-2">JPG, PNG or WebP, up to 4 MB each.</div>
        <button class="btn btn-sm btn-gold">Upload</button>
      </form>
      <div class="row g-2">
        <?php foreach ($images as $im): ?>
          <div class="col-6">
            <img src="<?= e(UPLOAD_URL . $im['filename']) ?>" class="w-100 rounded" style="height:100px;object-fit:cover" alt="">
            <div class="d-flex gap-1 mt-1 align-items-center">
              <?php if ($im['is_cover']): ?>
                <span class="badge text-bg-success">Cover</span>
              <?php else: ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cover"><input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>"><button class="btn btn-sm btn-outline-secondary py-0">Set cover</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Delete this photo?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0">Delete</button></form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$images): ?><div class="col-12 text-muted small">No photos yet.</div><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
