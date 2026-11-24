<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';

$home = db()->query("SELECT title, content FROM pages WHERE slug = 'home'")->fetch();

$rooms = db()->query(
    "SELECT rt.id, rt.name, rt.slug, rt.price_per_night, rt.capacity, rt.beds,
            (SELECT filename FROM room_images ri WHERE ri.room_type_id = rt.id
             ORDER BY ri.is_cover DESC, ri.sort_order LIMIT 1) AS cover
     FROM room_types rt WHERE rt.is_active = 1 ORDER BY rt.price_per_night LIMIT 8"
)->fetchAll();

$offer = db()->query(
    "SELECT * FROM offers WHERE is_active = 1
       AND (valid_from IS NULL OR valid_from <= CURDATE())
       AND (valid_to IS NULL OR valid_to >= CURDATE())
     ORDER BY discount_percent DESC LIMIT 1"
)->fetch();

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <h1>Your calm stay<br>by the lake</h1>
    <p class="lead mt-3"><?= e($home['content'] ?? '') ?></p>
    <a href="<?= url('rooms.php') ?>" class="btn btn-gold btn-lg mt-3">Explore rooms</a>
  </div>
</section>

<div class="container">
  <form class="search-card" action="<?= url('rooms.php') ?>" method="get">
    <div class="row g-3 align-items-end">
      <div class="col-md-3 col-6">
        <label for="check_in">Check-in</label>
        <input type="date" id="check_in" name="check_in" class="form-control" required>
      </div>
      <div class="col-md-3 col-6">
        <label for="check_out">Check-out</label>
        <input type="date" id="check_out" name="check_out" class="form-control" required>
      </div>
      <div class="col-md-3 col-6">
        <label for="guests">Guests</label>
        <select id="guests" name="guests" class="form-select">
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <option value="<?= $i ?>"><?= $i ?> guest<?= $i > 1 ? 's' : '' ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3 col-6 d-grid">
        <button class="btn btn-gold btn-lg">Check availability</button>
      </div>
    </div>
  </form>
</div>

<section class="container my-5 pt-4">
  <div class="text-center mb-4">
    <h2 class="section-title">Our rooms</h2>
    <p class="section-sub">From budget dorm beds to a family suite.</p>
  </div>
  <div class="row g-4">
    <?php foreach ($rooms as $r): ?>
      <div class="col-sm-6 col-lg-3">
        <div class="card room-card">
          <?php if ($r['cover']): ?>
            <img class="room-img" src="<?= e(UPLOAD_URL . $r['cover']) ?>" alt="<?= e($r['name']) ?>">
          <?php else: ?>
            <div class="room-img placeholder-img"><?= e($r['name']) ?></div>
          <?php endif; ?>
          <div class="card-body">
            <h5 class="card-title"><?= e($r['name']) ?></h5>
            <p class="text-muted small mb-2">Up to <?= (int)$r['capacity'] ?> guest<?= $r['capacity'] > 1 ? 's' : '' ?> &middot; <?= e($r['beds']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="price"><?= money($r['price_per_night']) ?> <small>/ night</small></span>
              <a href="<?= url('room.php?slug=' . urlencode($r['slug'])) ?>" class="btn btn-outline-gold btn-sm">View</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($offer): ?>
<section class="container my-5">
  <div class="offer-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <h3 class="mb-1"><?= e($offer['title']) ?></h3>
      <p class="mb-0 opacity-75"><?= e($offer['description']) ?></p>
    </div>
    <span class="badge badge-gold">-<?= (int)$offer['discount_percent'] ?>%</span>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
