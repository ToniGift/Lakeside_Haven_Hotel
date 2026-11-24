<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$pageTitle = 'My profile';

$errors = [];
$name = $user['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'details') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Please enter your name (max 100 characters).';
        } else {
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $user['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $hash = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $hash->execute([$user['id']]);
        if (!password_verify($current, (string)$hash->fetchColumn())) {
            $errors[] = 'Your current password is wrong.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h1 class="section-title mb-4">My profile</h1>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <div class="row g-4">
    <div class="col-lg-6">
      <form method="post" class="bg-white rounded-3 shadow-sm p-4">
        <?= csrf_field() ?><input type="hidden" name="action" value="details">
        <h5>Your details</h5>
        <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($user['email']) ?>" disabled></div>
        <div class="mb-3"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" value="<?= e($name) ?>" required></div>
        <button class="btn btn-gold">Save</button>
      </form>
    </div>
    <div class="col-lg-6">
      <form method="post" class="bg-white rounded-3 shadow-sm p-4">
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <h5>Change password</h5>
        <div class="mb-3"><label class="form-label" for="current">Current password</label><input class="form-control" type="password" id="current" name="current" required></div>
        <div class="mb-3"><label class="form-label" for="new">New password</label><input class="form-control" type="password" id="new" name="new" minlength="8" required></div>
        <div class="mb-3"><label class="form-label" for="confirm">Confirm new password</label><input class="form-control" type="password" id="confirm" name="confirm" required></div>
        <button class="btn btn-gold">Change password</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
