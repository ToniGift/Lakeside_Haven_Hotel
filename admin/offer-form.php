<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$offer = ['title' => '', 'description' => '', 'discount_percent' => 10, 'valid_from' => '', 'valid_to' => '', 'is_active' => 1];
if ($id) {
    $s = db()->prepare('SELECT * FROM offers WHERE id = ?');
    $s->execute([$id]);
    $row = $s->fetch();
    if (!$row) {
        flash('danger', 'Offer not found.');
        redirect('admin/offers.php');
    }
    $offer = array_map(fn($v) => $v ?? '', $row);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $offer = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'discount_percent' => trim($_POST['discount_percent'] ?? ''),
        'valid_from' => trim($_POST['valid_from'] ?? ''),
        'valid_to' => trim($_POST['valid_to'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    $isDate = fn($d) => ($x = DateTime::createFromFormat('Y-m-d', $d)) && $x->format('Y-m-d') === $d;

    if ($offer['title'] === '' || mb_strlen($offer['title']) > 150) $errors[] = 'Title is required (max 150 characters).';
    if (!ctype_digit($offer['discount_percent']) || (int)$offer['discount_percent'] < 1 || (int)$offer['discount_percent'] > 90) $errors[] = 'Discount must be a whole number from 1 to 90.';
    if ($offer['valid_from'] !== '' && !$isDate($offer['valid_from'])) $errors[] = 'Start date is not valid.';
    if ($offer['valid_to'] !== '' && !$isDate($offer['valid_to'])) $errors[] = 'End date is not valid.';
    if (!$errors && $offer['valid_from'] !== '' && $offer['valid_to'] !== '' && $offer['valid_to'] < $offer['valid_from']) $errors[] = 'End date must not be before the start date.';

    if (!$errors) {
        $p = [$offer['title'], $offer['description'], (int)$offer['discount_percent'],
              $offer['valid_from'] ?: null, $offer['valid_to'] ?: null, $offer['is_active']];
        if ($id) {
            db()->prepare('UPDATE offers SET title=?, description=?, discount_percent=?, valid_from=?, valid_to=?, is_active=? WHERE id=?')->execute([...$p, $id]);
        } else {
            db()->prepare('INSERT INTO offers (title, description, discount_percent, valid_from, valid_to, is_active) VALUES (?,?,?,?,?,?)')->execute($p);
        }
        flash('success', 'Offer saved.');
        redirect('admin/offers.php');
    }
}

$adminPage = 'offers';
$pageTitle = $id ? 'Edit offer' : 'New offer';
include __DIR__ . '/_header.php';
?>
<a href="<?= url('admin/offers.php') ?>" class="small">&larr; All offers</a>
<h1 class="mb-4 mt-2"><?= $id ? 'Edit offer' : 'New offer' ?></h1>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" class="panel" style="max-width:640px">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($offer['title']) ?>" required></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($offer['description']) ?></textarea></div>
    <div class="col-md-4"><label class="form-label">Discount (%)</label><input class="form-control" type="number" min="1" max="90" name="discount_percent" value="<?= e($offer['discount_percent']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Valid from</label><input class="form-control" type="date" name="valid_from" value="<?= e($offer['valid_from']) ?>"></div>
    <div class="col-md-4"><label class="form-label">Valid to</label><input class="form-control" type="date" name="valid_to" value="<?= e($offer['valid_to']) ?>"></div>
    <div class="col-12 form-text mt-0">Leave both dates empty for an offer that is always on.</div>
    <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="is_active" id="act" <?= $offer['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="act">Active</label></div>
    <div class="col-12"><button class="btn btn-gold">Save offer</button></div>
  </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
