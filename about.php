<?php
require_once __DIR__ . '/includes/auth.php';

$page = db()->query("SELECT title, content FROM pages WHERE slug = 'about'")->fetch();
$pageTitle = $page['title'] ?? 'About';
$roomCount = (int)db()->query("SELECT COUNT(*) FROM rooms WHERE status = 'active'")->fetchColumn();
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <img class="about-photo mb-4" src="<?= url('assets/img/hotel/lobby.jpg') ?>" alt="The lobby of <?= e(SITE_NAME) ?>" style="object-position:center 60%">
      <h1 class="section-title"><?= e($page['title'] ?? 'About Us') ?></h1>
      <div class="fs-5 text-muted"><?= nl2br(e($page['content'] ?? '')) ?></div>

      <div class="row g-3 mt-4 text-center">
        <div class="col-4"><div class="p-3 bg-cream rounded-3"><div class="price"><?= $roomCount ?></div><div class="small text-muted">Rooms &amp; beds</div></div></div>
        <div class="col-4"><div class="p-3 bg-cream rounded-3"><div class="price">24/7</div><div class="small text-muted">Reception</div></div></div>
        <div class="col-4"><div class="p-3 bg-cream rounded-3"><div class="price">Free</div><div class="small text-muted">Wi-Fi</div></div></div>
      </div>
      <a href="<?= url('rooms.php') ?>" class="btn btn-gold mt-4">See our rooms</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
