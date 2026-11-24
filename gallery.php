<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Gallery';

// Photos of the hotel itself (static files in assets/img/hotel/)
$hotel = [
    ['file' => 'lobby.jpg', 'caption' => 'Our lobby'],
];

// Photos shared by several rooms get a general caption instead of one room's name
$sharedCaptions = [
    'demo-breakfast.jpg' => 'Breakfast with a view',
    'demo-bathroom.jpg'  => 'Bright, clean bathrooms',
];

// Room photos uploaded in the admin area. A photo used by several rooms is listed once.
$rooms = db()->query(
    'SELECT ri.filename, MIN(rt.name) AS name, MIN(rt.slug) AS slug, COUNT(DISTINCT rt.id) AS rooms
       FROM room_images ri JOIN room_types rt ON rt.id = ri.room_type_id
      WHERE rt.is_active = 1
      GROUP BY ri.filename
      ORDER BY MIN(rt.id), MIN(ri.is_cover) DESC, MIN(ri.sort_order)'
)->fetchAll();

$items = [];
foreach ($hotel as $h) {
    $items[] = ['src' => url('assets/img/hotel/' . $h['file']), 'caption' => $h['caption'], 'link' => null];
}
foreach ($rooms as $r) {
    $shared = (int)$r['rooms'] > 1;
    $items[] = [
        'src'     => UPLOAD_URL . $r['filename'],
        'caption' => $shared ? ($sharedCaptions[$r['filename']] ?? 'Shared by several rooms') : $r['name'],
        'link'    => $shared ? null : url('room.php?slug=' . urlencode($r['slug'])),
    ];
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h1 class="section-title mb-1">Gallery</h1>
  <p class="section-sub">A look at our rooms and our hotel. Click a photo to enlarge it.</p>

  <div class="row g-3 mt-2">
    <?php foreach ($items as $i => $it): ?>
      <div class="col-6 col-md-4">
        <a href="#" class="gallery-tile d-block position-relative overflow-hidden rounded-3"
           data-bs-toggle="modal" data-bs-target="#lightbox"
           data-src="<?= e($it['src']) ?>" data-caption="<?= e($it['caption']) ?>" data-link="<?= e($it['link'] ?? '') ?>">
          <img src="<?= e($it['src']) ?>" alt="<?= e($it['caption']) ?>" loading="lazy" class="w-100" style="object-fit:cover">
          <span class="gallery-caption"><?= e($it['caption']) ?></span>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="modal fade" id="lightbox" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark text-white border-0">
      <div class="modal-body p-2 text-center">
        <img id="lightbox-img" src="" alt="" class="img-fluid rounded" style="max-height:78vh">
        <div class="d-flex justify-content-between align-items-center px-2 pt-2">
          <span id="lightbox-caption"></span>
          <span>
            <a id="lightbox-link" href="#" class="btn btn-sm btn-gold d-none">View room</a>
            <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Close</button>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('lightbox').addEventListener('show.bs.modal', function (ev) {
  var t = ev.relatedTarget;
  document.getElementById('lightbox-img').src = t.dataset.src;
  document.getElementById('lightbox-img').alt = t.dataset.caption;
  document.getElementById('lightbox-caption').textContent = t.dataset.caption;
  var l = document.getElementById('lightbox-link');
  if (t.dataset.link) { l.href = t.dataset.link; l.classList.remove('d-none'); } else { l.classList.add('d-none'); }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
