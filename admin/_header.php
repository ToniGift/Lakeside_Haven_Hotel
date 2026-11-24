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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/admin.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrap">
  <aside class="admin-side">
    <a class="brand d-block mb-4" href="<?= url('admin/') ?>"><?= e(SITE_NAME) ?></a>
    <nav class="nav flex-column">
      <?php foreach ($nav as $key => [$label, $path]): ?>
        <a class="nav-link <?= $adminPage === $key ? 'active' : '' ?>" href="<?= url($path) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <hr>
    <a class="nav-link" href="<?= url() ?>">View site</a>
    <a class="nav-link" href="<?= url('logout.php') ?>">Log out</a>
  </aside>
  <main class="admin-main">
    <?php foreach (get_flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show">
        <?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
