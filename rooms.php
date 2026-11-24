<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Rooms';

$in     = trim($_GET['check_in'] ?? '');
$out    = trim($_GET['check_out'] ?? '');
$maxCap = max_capacity();
[$adults, $children] = read_party($maxCap);
$guests = $adults + $children;       // total people, used to hide rooms that are too small
$maxPrice = (float)($_GET['max_price'] ?? 0);
$sort   = $_GET['sort'] ?? 'price_asc';

$hasDates  = ($in !== '' || $out !== '');
$dateError = $hasDates ? validate_dates($in, $out) : null;
$searching = $hasDates && !$dateError;
$nights    = $searching ? nights_between($in, $out) : 0;

$order = ['price_asc' => 'rt.price_per_night ASC', 'price_desc' => 'rt.price_per_night DESC',
          'capacity' => 'rt.capacity DESC'][$sort] ?? 'rt.price_per_night ASC';

$sql = "SELECT rt.*, (SELECT filename FROM room_images ri WHERE ri.room_type_id = rt.id
                      ORDER BY ri.is_cover DESC, ri.sort_order LIMIT 1) AS cover
        FROM room_types rt WHERE rt.is_active = 1 AND rt.capacity >= ?";
$params = [$guests];
if ($maxPrice > 0) {
    $sql .= ' AND rt.price_per_night <= ?';
    $params[] = $maxPrice;
}
$sql .= " ORDER BY $order";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$types = $stmt->fetchAll();

foreach ($types as &$t) {
    $t['available'] = $searching ? count(available_rooms((int)$t['id'], $in, $out)) : null;
}
unset($t);
$photos = room_photos_map(array_column($types, 'id'));

$discount = current_discount();
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h1 class="section-title mb-1">Our rooms</h1>
  <p class="section-sub">Choose your dates to see what is available.</p>

  <form class="search-card mt-3 mb-4" method="get" style="margin-top:0!important">
    <div class="row g-3 align-items-end">
      <div class="col-lg-2 col-6">
        <label for="check_in">Check-in</label>
        <input type="date" id="check_in" name="check_in" class="form-control" value="<?= e($in) ?>">
      </div>
      <div class="col-lg-2 col-6">
        <label for="check_out">Check-out</label>
        <input type="date" id="check_out" name="check_out" class="form-control" value="<?= e($out) ?>">
      </div>
      <div class="col-lg-1 col-6"><?= party_select('adults', $adults, $maxCap) ?></div>
      <div class="col-lg-1 col-6"><?= party_select('children', $children, $maxCap) ?></div>
      <div class="col-lg-2 col-6">
        <label for="max_price">Max price</label>
        <input type="number" min="0" step="5" id="max_price" name="max_price" class="form-control"
               value="<?= $maxPrice > 0 ? e($maxPrice) : '' ?>" placeholder="any">
      </div>
      <div class="col-lg-2 col-6">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort" class="form-select">
          <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
          <option value="capacity"   <?= $sort === 'capacity'   ? 'selected' : '' ?>>Capacity</option>
        </select>
      </div>
      <div class="col-lg-2 col-6 d-grid">
        <button class="btn btn-gold">Search</button>
      </div>
    </div>
  </form>

  <?php if ($dateError): ?>
    <div class="alert alert-warning"><?= e($dateError) ?></div>
  <?php elseif ($searching): ?>
    <p class="text-muted"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?>, <?= e($in) ?> to <?= e($out) ?>, <?= e(party_label($adults, $children)) ?></p>
  <?php endif; ?>

  <?php if (!$types): ?>
    <div class="alert alert-info">No rooms match your filters. Try a higher price or fewer guests.</div>
  <?php endif; ?>

  <div class="row g-4">
    <?php foreach ($types as $r):
        $soldOut = $searching && $r['available'] === 0;
        $query = $searching ? '&check_in=' . urlencode($in) . '&check_out=' . urlencode($out) . '&adults=' . $adults . '&children=' . $children : '';
        $price = (float)$r['price_per_night'];
    ?>
      <div class="col-md-6 col-lg-4">
        <div class="card room-card <?= $soldOut ? 'opacity-75' : '' ?>">
          <?= room_media_html($r['name'], $photos[(int)$r['id']] ?? [], 'rc-' . (int)$r['id']) ?>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= e($r['name']) ?></h5>
            <p class="text-muted small mb-2">
              Up to <?= (int)$r['capacity'] ?> guest<?= $r['capacity'] > 1 ? 's' : '' ?> &middot; <?= e($r['beds']) ?>
              <?php if ($r['size_m2']): ?>&middot; <?= (int)$r['size_m2'] ?> m&sup2;<?php endif; ?>
            </p>
            <p class="small"><?= e(mb_strimwidth($r['description'] ?? '', 0, 100, '...')) ?></p>
            <div class="mt-auto">
              <?php if ($searching): ?>
                <?php if ($soldOut): ?>
                  <span class="badge bg-secondary mb-2">Not available for these dates</span>
                <?php else: ?>
                  <span class="badge bg-success mb-2"><?= $r['available'] ?> available</span>
                <?php endif; ?>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center">
                <span class="price"><?= money($price) ?> <small>/ night</small></span>
                <a href="<?= url('room.php?slug=' . urlencode($r['slug']) . $query) ?>" class="btn btn-outline-gold btn-sm">Details</a>
              </div>
              <?php if ($searching): ?>
                <div class="small text-muted mt-1">Total for <?= $nights ?> night<?= $nights > 1 ? 's' : '' ?>: <strong><?= money($price * $nights) ?></strong></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
