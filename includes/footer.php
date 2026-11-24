<footer class="site-footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-md-5">
        <div class="brand mb-2"><?= e(SITE_NAME) ?></div>
        <p class="mb-0">A small hotel and hostel with clean rooms, fair prices and a warm welcome.</p>
      </div>
      <div class="col-md-3">
        <h6>Explore</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= url('rooms.php') ?>">Rooms</a></li>
          <li><a href="<?= url('gallery.php') ?>">Gallery</a></li>
          <li><a href="<?= url('about.php') ?>">About</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6>Contact</h6>
        <p class="mb-0">12 Lake Street<br>info@lakesidehaven.example<br>+48 000 000 000</p>
      </div>
    </div>
    <hr>
    <div class="small">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
