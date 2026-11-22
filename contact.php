<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Contact';

$errors = [];
$name = $email = $message = '';
$user = current_user();
if ($user) { $name = $user['name']; $email = $user['email']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($message) < 5 || mb_strlen($message) > 3000) $errors[] = 'Message must be between 5 and 3000 characters.';

    if (!$errors) {
        db()->prepare('INSERT INTO messages (name, email, message) VALUES (?, ?, ?)')->execute([$name, $email, $message]);
        flash('success', 'Thank you! Your message has been sent.');
        redirect('contact.php');
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <div class="row g-5">
    <div class="col-lg-5">
      <h1 class="section-title">Contact us</h1>
      <p class="section-sub">Questions about a room or your stay? Send us a message.</p>
      <p class="mb-1"><strong>Address</strong><br>12 Lake Street</p>
      <p class="mb-1 mt-3"><strong>Email</strong><br>info@lakesidehaven.example</p>
      <p class="mt-3"><strong>Phone</strong><br>+48 000 000 000</p>
      <p><strong>Reception</strong><br>Open 24 hours</p>
    </div>
    <div class="col-lg-7">
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form method="post" class="search-card" style="margin-top:0">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6"><label for="name">Name</label><input class="form-control" id="name" name="name" value="<?= e($name) ?>" required></div>
          <div class="col-md-6"><label for="email">Email</label><input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required></div>
          <div class="col-12"><label for="message">Message</label><textarea class="form-control" id="message" name="message" rows="6" required><?= e($message) ?></textarea></div>
          <div class="col-12"><button class="btn btn-gold">Send message</button></div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
