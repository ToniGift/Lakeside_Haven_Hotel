<?php
require_once __DIR__ . '/auth.php';
$user = current_user();
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME;
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand brand" href="<?= url() ?>"><?= e(SITE_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="<?= url() ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('rooms.php') ?>">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('gallery.php') ?>">Gallery</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('about.php') ?>">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('contact.php') ?>">Contact</a></li>
        <?php if ($user): ?>
          <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/') ?>">Admin</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('my-bookings.php') ?>">My bookings</a></li>
          <?php endif; ?>
          <li class="nav-item ms-lg-2"><a class="nav-link text-muted small" href="<?= url('profile.php') ?>">Hi, <?= e($user['name']) ?></a></li>
          <li class="nav-item"><a class="btn btn-outline-gold btn-sm" href="<?= url('logout.php') ?>">Log out</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Log in</a></li>
          <li class="nav-item"><a class="btn btn-gold btn-sm" href="<?= url('register.php') ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php $flashes = get_flashes(); if ($flashes): ?>
  <div class="container mt-3">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
