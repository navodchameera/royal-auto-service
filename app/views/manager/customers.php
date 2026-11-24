<?php
defined('APP_PATH') or exit;
$title     = 'Customers';
$navActive = 'customers';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Customers</h1>
    <div class="sub">View, update and ban customer accounts.</div>
  </div>
  <form class="search" method="get" action="<?= BASE_URL ?>/index.php" role="search">
    <input type="hidden" name="url" value="manage-customers">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input class="in" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone" aria-label="Search customers">
    <button class="btn" type="submit">Search</button>
  </form>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter by status">
  <?php foreach ($statuses as $key => $label): ?>
    <a href="<?= e($base) ?>manage-customers&amp;status=<?= e($key) ?>&amp;q=<?= e(urlencode($q)) ?>"<?= $key === $status ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card">
  <?php if ($customers): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Customer</th><th>Contact</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): $id = (int)$c['user_id']; $banned = $c['status'] === 'blocked'; ?>
          <tr>
            <td><b><?= e($c['full_name']) ?></b><span class="m">@<?= e($c['username']) ?></span></td>
            <td><?= e($c['email']) ?><br><span class="m"><?= e($c['phone']) ?></span></td>
            <td><?= (int)$c['bookings'] ?></td>
            <td><span class="pill<?= $banned ? ' warn' : '' ?>"><?= $banned ? 'Banned' : 'Active' ?></span></td>
            <td>
              <div class="acts">
                <a class="btn g" href="<?= e($base) ?>manage-customers&amp;id=<?= $id ?>">View</a>
                <form method="post" action="<?= e($base) ?>manage-customers&amp;status=<?= e($status) ?>&amp;q=<?= e(urlencode($q)) ?>"
                      <?= $banned ? '' : 'data-confirm="Ban ' . e($c['full_name']) . '? They will be signed out and cannot log in."' ?>>
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="<?= $banned ? 'unban' : 'ban' ?>">
                  <input type="hidden" name="customer_id" value="<?= $id ?>">
                  <button class="btn <?= $banned ? '' : 'danger' ?>" type="submit"><?= $banned ? 'Unban' : 'Ban' ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty">No customers match this search.</p>
  <?php endif; ?>
</section>
<script src="<?= BASE_URL ?>/assets/js/confirm.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
