<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    db()->prepare('DELETE FROM messages WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('success', 'Message deleted.');
    redirect('admin/messages.php');
}

$messages = db()->query('SELECT * FROM messages ORDER BY created_at DESC, id DESC LIMIT 200')->fetchAll();

$adminPage = 'messages';
$pageTitle = 'Messages';
include __DIR__ . '/_header.php';
?>
<h1 class="mb-3">Messages <span class="text-muted fs-6">(<?= count($messages) ?>)</span></h1>
<?php if (!$messages): ?>
  <div class="panel text-muted">No messages yet. Messages sent from the Contact page appear here.</div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
  <div class="panel mb-3">
    <div class="d-flex justify-content-between flex-wrap gap-2">
      <div>
        <strong><?= e($m['name']) ?></strong>
        <a class="ms-2" href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
      </div>
      <div class="small text-muted"><?= e($m['created_at']) ?></div>
    </div>
    <p class="mt-2 mb-3"><?= nl2br(e($m['message'])) ?></p>
    <a class="btn btn-sm btn-outline-gold" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: your message to ' . SITE_NAME) ?>">Reply by email</a>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this message?')">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <button class="btn btn-sm btn-outline-danger">Delete</button>
    </form>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/_footer.php'; ?>
