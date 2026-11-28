<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Register';

if (is_logged_in()) {
    redirect('');
}

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name (max 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $newId = (int)db()->lastInsertId();
        crm_sync_user($newId);
        login_user($newId);
        flash('success', 'Welcome, ' . $name . '! Your account has been created.');
        $next = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login']);
        header('Location: ' . ($next && str_starts_with($next, BASE_URL . '/') ? $next : url('')));
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-card">
    <h2 class="mb-1">Create account</h2>
    <p class="text-muted">Register to book a room.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="name">Full name</label>
        <input class="form-control" id="name" name="name" value="<?= e($name) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" minlength="8" required>
        <div class="form-text">At least 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="confirm">Confirm password</label>
        <input class="form-control" type="password" id="confirm" name="confirm" required>
      </div>
      <button class="btn btn-gold w-100">Register</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="<?= url('login.php') ?>">Log in</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
