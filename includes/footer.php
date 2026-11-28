<?php
$ic = fn(string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$socials = [
    'Facebook'  => '<path d="M15 3h-2.5A4.5 4.5 0 0 0 8 7.5V10H5v4h3v7h4v-7h3l1-4h-4V7.5a.5.5 0 0 1 .5-.5H15z"/>',
    'Instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6" fill="currentColor"/>',
    'X'         => '<path d="M4 4l16 16M20 4L4 20"/>',
    'YouTube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9.5v5l4.5-2.5z"/>',
];
?>
<footer class="site-footer mt-5">
  <div class="footer-cta">
    <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
      <div>
        <h3 class="mb-1">Ready for a stay by the lake?</h3>
        <p class="mb-0">Pick your dates and book in a minute. Free Wi-Fi and a 24/7 reception.</p>
      </div>
      <a href="<?= url('rooms.php') ?>" class="btn btn-gold btn-lg flex-shrink-0">Book a room</a>
    </div>
  </div>

  <div class="container footer-main">
    <div class="row g-4 g-lg-5">
      <div class="col-12 col-lg-4">
        <div class="brand mb-3"><?= e(SITE_NAME) ?></div>
        <p class="footer-about">A small hotel and hostel with clean rooms, fair prices and a warm welcome.</p>
        <ul class="social list-unstyled d-flex flex-wrap gap-2 mb-0" aria-label="Social media">
          <?php foreach ($socials as $label => $path): ?>
            <li><span class="social-icon" role="img" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><?= $ic($path) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="col-6 col-md-4 col-lg-2">
        <h6>Explore</h6>
        <ul class="list-unstyled footer-links mb-0">
          <li><a href="<?= url('rooms.php') ?>">Rooms</a></li>
          <li><a href="<?= url('gallery.php') ?>">Gallery</a></li>
          <li><a href="<?= url('about.php') ?>">About</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-4 col-lg-2">
        <h6>Guests</h6>
        <ul class="list-unstyled footer-links mb-0">
          <?php if (!empty($user)): ?>
            <li><a href="<?= url($user['role'] === 'admin' ? 'admin/' : 'my-bookings.php') ?>"><?= $user['role'] === 'admin' ? 'Admin' : 'My bookings' ?></a></li>
            <li><a href="<?= url('profile.php') ?>">My profile</a></li>
          <?php else: ?>
            <li><a href="<?= url('login.php') ?>">Log in</a></li>
            <li><a href="<?= url('register.php') ?>">Create account</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-12 col-md-4 col-lg-4">
        <h6>Contact</h6>
        <ul class="list-unstyled footer-contact mb-0">
          <li><?= $ic('<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>') ?><span>12 Lake Street</span></li>
          <li><?= $ic('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>') ?><a href="mailto:info@lakesidehaven.example">info@lakesidehaven.example</a></li>
          <li><?= $ic('<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>') ?><a href="tel:+48000000000">+48 000 000 000</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <a href="#top" class="to-top">Back to top <?= $ic('<path d="M12 19V5M5 12l7-7 7 7"/>') ?></a>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
