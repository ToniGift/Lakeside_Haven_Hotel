<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Log in';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/' : '');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, name, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        login_user((int)$row['id']);
        flash('success', 'Welcome back, ' . $row['name'] . '!');
        $next = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login']);
        if ($row['role'] === 'admin') {
            redirect('admin/');
        }
        // only follow redirects that stay inside this site
        header('Location: ' . ($next && str_starts_with($next, BASE_URL . '/') ? $next : url('')));
        exit;
    }
    $error = 'Wrong email or password.';
}

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-card">
    <h2 class="mb-1">Log in</h2>
    <p class="text-muted">Log in to book a room and manage your bookings.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-gold w-100">Log in</button>
    </form>
    <p class="text-center mt-3 mb-0 small">No account yet? <a href="<?= url('register.php') ?>">Register</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
