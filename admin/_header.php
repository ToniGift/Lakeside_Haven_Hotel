<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();                      // every admin page is protected here
$adminPage = $adminPage ?? '';
$pageTitle = isset($pageTitle) ? $pageTitle . ' | Admin' : 'Admin';
$nav = [
    'dashboard' => ['Dashboard', 'admin/'],
    'rooms'     => ['Rooms', 'admin/rooms.php'],
    'bookings'  => ['Bookings', 'admin/bookings.php'],
    'users'     => ['Users', 'admin/users.php'],
    'pages'     => ['Pages', 'admin/pages.php'],
    'offers'    => ['Offers', 'admin/offers.php'],
    'messages'  => ['Messages', 'admin/messages.php'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#14213d">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/admin.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrap">
  <aside class="admin-side">
    <div class="admin-side-top d-flex justify-content-between align-items-center">
      <a class="brand" href="<?= url('admin/') ?>"><?= e(SITE_NAME) ?></a>
      <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse"
              data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle admin menu">&#9776; Menu</button>
    </div>
    <div class="collapse d-lg-block" id="adminNav">
      <nav class="nav flex-column mt-lg-4 mt-3">
        <?php foreach ($nav as $key => [$label, $path]): ?>
          <a class="nav-link <?= $adminPage === $key ? 'active' : '' ?>" href="<?= url($path) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <hr>
      <a class="nav-link" href="<?= url() ?>">View site</a>
      <a class="nav-link" href="<?= url('logout.php') ?>">Log out</a>
    </div>
  </aside>
  <main class="admin-main">
    <?php foreach (get_flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show">
        <?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
