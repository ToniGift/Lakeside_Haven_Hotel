<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pages = db()->query('SELECT * FROM pages ORDER BY id')->fetchAll();
$bySlug = array_column($pages, null, 'slug');
$slug = $_GET['slug'] ?? ($pages[0]['slug'] ?? '');
if (!isset($bySlug[$slug])) {
    $slug = $pages[0]['slug'] ?? '';
}
$errors = [];
$page = $bySlug[$slug] ?? null;

if ($page && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title === '' || mb_strlen($title) > 150) $errors[] = 'Title is required (max 150 characters).';
    if (mb_strlen($content) > 10000) $errors[] = 'Content is too long (max 10000 characters).';
    if (!$errors) {
        db()->prepare('UPDATE pages SET title = ?, content = ? WHERE slug = ?')->execute([$title, $content, $slug]);
        flash('success', 'Page saved.');
        redirect('admin/pages.php?slug=' . urlencode($slug));
    }
    $page['title'] = $title;
    $page['content'] = $content;
}

$adminPage = 'pages';
$pageTitle = 'Pages';
include __DIR__ . '/_header.php';
?>
<h1 class="mb-3">Pages</h1>
<ul class="nav nav-pills mb-3">
  <?php foreach ($pages as $p): ?>
    <li class="nav-item"><a class="nav-link <?= $p['slug'] === $slug ? 'active bg-dark' : 'text-dark' ?>" href="<?= url('admin/pages.php?slug=' . urlencode($p['slug'])) ?>"><?= e(ucfirst($p['slug'])) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($page): ?>
<form method="post" class="panel" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($page['title']) ?>" required></div>
  <div class="mb-3">
    <label class="form-label">Content</label>
    <textarea class="form-control" name="content" rows="10"><?= e($page['content']) ?></textarea>
    <div class="form-text">
      <?= $slug === 'home' ? 'Shown in the large intro text at the top of the home page.' : 'Shown on the About page. Line breaks are kept.' ?> Plain text only.
    </div>
  </div>
  <button class="btn btn-gold">Save page</button>
  <a class="btn btn-link" target="_blank" href="<?= url($slug === 'home' ? '' : $slug . '.php') ?>">View page</a>
</form>
<?php else: ?>
  <p class="text-muted">No pages found.</p>
<?php endif; ?>
<?php include __DIR__ . '/_footer.php'; ?>
