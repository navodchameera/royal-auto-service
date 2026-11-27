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
    <div class="sub">View, update, ban or delete customer accounts.</div>
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
        <?php foreach ($customers as $c):
            $id      = (int)$c['user_id'];
            $banned  = $c['status'] === 'blocked';
            $name    = $c['full_name'];
            $postUrl = $base . 'manage-customers&status=' . $status . '&q=' . urlencode($q);
        ?>
          <tr>
            <td><b><?= e($name) ?></b><span class="m">@<?= e($c['username']) ?></span></td>
            <td><?= e($c['email']) ?><br><span class="m"><?= e($c['phone']) ?></span></td>
            <td><?= (int)$c['bookings'] ?></td>
            <td><span class="pill<?= $banned ? ' warn' : '' ?>"><?= $banned ? 'Banned' : 'Active' ?></span></td>
            <td>
              <div class="acts nowrap">
                <a class="btn g" href="<?= e($base) ?>manage-customers&amp;id=<?= $id ?>">View</a>
                <?= Ui::rowMenu('menu_c' . $id, 'More actions for ' . $name, [
                    $banned
                      ? ['label' => 'Unban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'unban', 'customer_id' => $id],
                         'title' => 'Unban ' . $name . '?', 'text' => $name . ' will be able to log in again.', 'ok' => 'Unban customer']
                      : ['label' => 'Ban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'ban', 'customer_id' => $id],
                         'title' => 'Ban ' . $name . '?', 'text' => $name . ' will be signed out and will not be able to log in. Their data and bookings are kept, and you can unban them later.',
                         'ok' => 'Ban customer', 'tone' => 'danger'],
                    ['label' => 'Delete', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'delete', 'customer_id' => $id],
                     'title' => 'Delete ' . $name . '?', 'text' => 'This permanently deletes ' . $name . ' and all their vehicles, bookings, photos and payments from the database. This cannot be undone.',
                     'ok' => 'Delete customer', 'tone' => 'danger', 'red' => true],
                ]) ?>
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
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
