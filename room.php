<?php
require_once __DIR__ . '/includes/auth.php';

$stmt = db()->prepare('SELECT * FROM room_types WHERE slug = ? AND is_active = 1');
$stmt->execute([$_GET['slug'] ?? '']);
$room = $stmt->fetch();
if (!$room) {
    http_response_code(404);
    $pageTitle = 'Room not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container py-5"><h1>Room not found</h1><a href="' . url('rooms.php') . '">Back to rooms</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $room['name'];

$imgs = db()->prepare('SELECT filename FROM room_images WHERE room_type_id = ? ORDER BY is_cover DESC, sort_order');
$imgs->execute([$room['id']]);
$images = $imgs->fetchAll();

$am = db()->prepare('SELECT a.name FROM amenities a JOIN room_type_amenities x ON x.amenity_id = a.id WHERE x.room_type_id = ? ORDER BY a.name');
$am->execute([$room['id']]);
$amenities = $am->fetchAll(PDO::FETCH_COLUMN);

$in     = trim($_GET['check_in'] ?? '');
$out    = trim($_GET['check_out'] ?? '');
$guests = max(1, min((int)$room['capacity'], (int)($_GET['guests'] ?? 1)));
$dateError = ($in !== '' || $out !== '') ? validate_dates($in, $out) : null;
$checked = ($in !== '' || $out !== '') && !$dateError;

$available = $checked ? available_rooms((int)$room['id'], $in, $out) : [];
$nights    = $checked ? nights_between($in, $out) : 0;
$subtotal  = $nights * (float)$room['price_per_night'];
$discount  = current_discount();
$total     = $subtotal * (100 - $discount) / 100;

$bookUrl = url('book.php?room_type=' . (int)$room['id'] . '&check_in=' . urlencode($in)
             . '&check_out=' . urlencode($out) . '&guests=' . $guests);

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <nav class="small mb-3"><a href="<?= url('rooms.php') ?>">&larr; All rooms</a></nav>
  <div class="row g-4">
    <div class="col-lg-7">
      <?php if ($images): ?>
        <div id="gal" class="carousel slide" data-bs-ride="false">
          <div class="carousel-inner rounded-3">
            <?php foreach ($images as $i => $im): ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= e(UPLOAD_URL . $im['filename']) ?>" class="d-block w-100" style="height:380px;object-fit:cover" alt="<?= e($room['name']) ?>">
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <button class="carousel-control-prev" data-bs-target="#gal" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" data-bs-target="#gal" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?= room_cover_html(null, $room['name'], 'rounded-3') ?>
      <?php endif; ?>

      <h1 class="mt-4"><?= e($room['name']) ?></h1>
      <p class="text-muted">
        Up to <?= (int)$room['capacity'] ?> guest<?= $room['capacity'] > 1 ? 's' : '' ?> &middot; <?= e($room['beds']) ?>
        <?php if ($room['size_m2']): ?>&middot; <?= (int)$room['size_m2'] ?> m&sup2;<?php endif; ?>
      </p>
      <p><?= nl2br(e($room['description'])) ?></p>

      <?php if ($amenities): ?>
        <h5 class="mt-4">Amenities</h5>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($amenities as $a): ?>
            <span class="badge rounded-pill text-bg-light border"><?= e($a) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-5">
      <div class="search-card" style="margin-top:0">
        <div class="price mb-3"><?= money($room['price_per_night']) ?> <small>/ night</small></div>
        <form method="get">
          <input type="hidden" name="slug" value="<?= e($room['slug']) ?>">
          <div class="row g-3">
            <div class="col-6">
              <label for="check_in">Check-in</label>
              <input type="date" id="check_in" name="check_in" class="form-control" value="<?= e($in) ?>" required>
            </div>
            <div class="col-6">
              <label for="check_out">Check-out</label>
              <input type="date" id="check_out" name="check_out" class="form-control" value="<?= e($out) ?>" required>
            </div>
            <div class="col-12">
              <label for="guests">Guests</label>
              <select id="guests" name="guests" class="form-select">
                <?php for ($i = 1; $i <= $room['capacity']; $i++): ?>
                  <option value="<?= $i ?>" <?= $guests === $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-12 d-grid"><button class="btn btn-outline-gold">Check availability</button></div>
          </div>
        </form>

        <hr>
        <?php if ($dateError): ?>
          <div class="alert alert-warning mb-0"><?= e($dateError) ?></div>
        <?php elseif ($checked && !$available): ?>
          <div class="alert alert-secondary mb-0">Sorry, no rooms of this type are available for those dates.</div>
        <?php elseif ($checked): ?>
          <div class="mb-2"><span class="badge bg-success"><?= count($available) ?> available</span></div>
          <div class="d-flex justify-content-between"><span><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?> &times; <?= money($room['price_per_night']) ?></span><span><?= money($subtotal) ?></span></div>
          <?php if ($discount > 0): ?>
            <div class="d-flex justify-content-between text-success"><span>Offer -<?= $discount ?>%</span><span>-<?= money($subtotal - $total) ?></span></div>
          <?php endif; ?>
          <div class="d-flex justify-content-between fw-bold fs-5 mt-1"><span>Total</span><span><?= money($total) ?></span></div>

          <?php if (is_logged_in()): ?>
            <a class="btn btn-gold w-100 mt-3" href="<?= $bookUrl ?>">Book now</a>
          <?php else: ?>
            <a class="btn btn-gold w-100 mt-3" href="<?= $bookUrl ?>">Log in to book</a>
            <p class="small text-muted text-center mt-2 mb-0">You need an account to book. <a href="<?= url('register.php') ?>">Register free</a></p>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted small mb-0">Pick your dates to see availability and the total price.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
