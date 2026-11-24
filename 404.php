<?php
require_once __DIR__ . '/includes/auth.php';
http_response_code(404);
$pageTitle = 'Page not found';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center">
  <h1 class="section-title" style="font-size:4rem">404</h1>
  <p class="section-sub fs-5">We could not find that page.</p>
  <a href="<?= url() ?>" class="btn btn-gold">Back to home</a>
  <a href="<?= url('rooms.php') ?>" class="btn btn-outline-gold">See rooms</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
