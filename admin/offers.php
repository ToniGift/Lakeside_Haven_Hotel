<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        db()->prepare('UPDATE offers SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Offer updated.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM offers WHERE id = ?')->execute([$id]);
        flash('success', 'Offer deleted.');
    }
    redirect('admin/offers.php');
}

$offers = db()->query('SELECT * FROM offers ORDER BY is_active DESC, id DESC')->fetchAll();
$today = date('Y-m-d');
function offer_state(array $o, string $today): array
{
    if (!$o['is_active']) return ['Off', 'secondary'];
    if ($o['valid_from'] && $o['valid_from'] > $today) return ['Scheduled', 'info'];
    if ($o['valid_to'] && $o['valid_to'] < $today) return ['Expired', 'dark'];
    return ['Live', 'success'];
}

$adminPage = 'offers';
$pageTitle = 'Offers';
include __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="mb-0">Offers</h1>
  <a href="<?= url('admin/offer-form.php') ?>" class="btn btn-gold">+ Add offer</a>
</div>
<p class="text-muted">A live offer is shown on the home page, and its discount is applied to every new booking (the highest live discount wins).</p>
<div class="panel">
  <?php if (!$offers): ?><p class="text-muted mb-0">No offers yet.</p><?php else: ?>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Title</th><th>Discount</th><th>Valid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($offers as $o): [$label, $color] = offer_state($o, $today); ?>
      <tr>
        <td><strong><?= e($o['title']) ?></strong><div class="small text-muted"><?= e(mb_strimwidth((string)$o['description'], 0, 80, '...')) ?></div></td>
        <td>-<?= (int)$o['discount_percent'] ?>%</td>
        <td class="small"><?= $o['valid_from'] || $o['valid_to'] ? e(($o['valid_from'] ?: 'any time') . ' to ' . ($o['valid_to'] ?: 'no end')) : 'Always' ?></td>
        <td><span class="badge text-bg-<?= $color ?>"><?= $label ?></span></td>
        <td class="text-end text-nowrap">
          <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $o['is_active'] ? 'Turn off' : 'Turn on' ?></button></form>
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/offer-form.php?id=' . (int)$o['id']) ?>">Edit</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this offer?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
