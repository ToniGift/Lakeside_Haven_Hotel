<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Gallery';

$images = db()->query(
    'SELECT ri.filename, rt.name FROM room_images ri JOIN room_types rt ON rt.id = ri.room_type_id
      WHERE rt.is_active = 1 ORDER BY rt.id, ri.sort_order'
)->fetchAll();
$types = db()->query('SELECT name, slug FROM room_types WHERE is_active = 1 ORDER BY id')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h1 class="section-title mb-1">Gallery</h1>
  <p class="section-sub">A look at our rooms.</p>

  <div class="row g-3 mt-2">
    <?php if ($images): ?>
      <?php foreach ($images as $im): ?>
        <div class="col-6 col-md-4">
          <a href="<?= e(UPLOAD_URL . $im['filename']) ?>" target="_blank">
            <img src="<?= e(UPLOAD_URL . $im['filename']) ?>" alt="<?= e($im['name']) ?>"
                 class="w-100 rounded-3" style="height:220px;object-fit:cover">
          </a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($types as $t): ?>
        <div class="col-6 col-md-4">
          <a href="<?= url('room.php?slug=' . urlencode($t['slug'])) ?>" class="text-decoration-none">
            <div class="room-img placeholder-img rounded-3"><?= e($t['name']) ?></div>
          </a>
        </div>
      <?php endforeach; ?>
      <div class="col-12"><p class="text-muted small mb-0">Photos will appear here once they are uploaded in the admin area.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
