<?php
// Placeholder - the real booking form and logic are built in phase 5.
// Already enforces the rule: only registered, logged-in users can book.
require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Book';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <h1>Booking</h1>
  <p class="text-muted">The booking form is coming in phase 5.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
