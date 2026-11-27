<?php
defined('APP_PATH') or exit;
$title     = $c['full_name'];
$navActive = 'customers';
$base      = BASE_URL . '/index.php?url=';
$id        = (int)$c['user_id'];
$banned    = $c['status'] === 'blocked';
require APP_PATH . '/views/layout/app_header.php';
$err = function ($key) use ($errors) { return e($errors[$key] ?? ''); };
?>
<?php
$name    = $c['full_name'];
$postUrl = $base . 'manage-customers&id=' . $id;
$extra   = $activeBookings > 0 ? ' They have ' . (int)$activeBookings . ' active booking' . ($activeBookings === 1 ? '' : 's') . '.' : '';
?>
<div class="top">
  <div>
    <a class="back" href="<?= e($base) ?>manage-customers">← All customers</a>
    <h1><?= e($c['full_name']) ?></h1>
    <div class="sub">@<?= e($c['username']) ?> · joined <?= e(date('j M Y', strtotime($c['created_at']))) ?> ·
      <span class="pill<?= $banned ? ' warn' : '' ?>"><?= $banned ? 'Banned' : 'Active' ?></span></div>
  </div>
  <?= Ui::rowMenu('menu_customer', 'More actions', [
      $banned
        ? ['label' => 'Unban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'unban', 'customer_id' => $id],
           'title' => 'Unban ' . $name . '?', 'text' => $name . ' will be able to log in again.', 'ok' => 'Unban customer']
        : ['label' => 'Ban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'ban', 'customer_id' => $id],
           'title' => 'Ban ' . $name . '?', 'text' => $name . ' will be signed out and will not be able to log in. Their data and bookings are kept.' . $extra,
           'ok' => 'Ban customer', 'tone' => 'danger'],
      ['label' => 'Delete', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'delete', 'customer_id' => $id],
       'title' => 'Delete ' . $name . '?', 'text' => 'This permanently deletes ' . $name . ' and all their vehicles, bookings, photos and payments from the database.' . $extra . ' This cannot be undone.',
       'ok' => 'Delete customer', 'tone' => 'danger', 'red' => true],
  ]) ?>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($formError !== ''): ?><div class="alert" role="alert"><?= e($formError) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:1.2fr 1fr;align-items:start">
  <div class="g2">
    <section class="card">
      <h2>Details</h2>
      <form method="post" action="<?= e($base) ?>manage-customers&amp;id=<?= $id ?>" data-validate data-mode="customer" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="update">

        <label for="full_name">Full name</label>
        <input class="in" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>">
        <p class="err" id="full_name-err" aria-live="polite"><?= $err('full_name') ?></p>

        <div class="two">
          <div>
            <label for="email">Email</label>
            <input class="in" id="email" name="email" type="email" value="<?= e($old['email']) ?>">
            <p class="err" id="email-err" aria-live="polite"><?= $err('email') ?></p>
          </div>
          <div>
            <label for="phone">Phone</label>
            <input class="in" id="phone" name="phone" type="tel" value="<?= e($old['phone']) ?>">
            <p class="err" id="phone-err" aria-live="polite"><?= $err('phone') ?></p>
          </div>
        </div>

        <label for="address">Address</label>
        <input class="in" id="address" name="address" value="<?= e($old['address']) ?>">
        <p class="err" id="address-err" aria-live="polite"><?= $err('address') ?></p>

        <button class="btn" type="submit">Save changes</button>
      </form>
    </section>

    <section class="card">
      <h2>Recent bookings</h2>
      <?php if ($recent): ?>
        <div class="lst">
          <?php foreach ($recent as $r): ?>
            <div class="row">
              <div class="g1">
                <b><a href="<?= e($base) ?>manage-bookings&amp;id=<?= (int)$r['booking_id'] ?>">#<?= (int)$r['booking_id'] ?></a> · <?= e($r['make_model']) ?></b>
                <span class="m"><?= e(date('D j M Y', strtotime($r['appointment_date']))) ?> · <?= e($r['package_name']) ?></span>
              </div>
              <span class="pill"><?= e(status_label($r['status'])) ?></span>
              <b class="price"><?= $r['quoted_price'] !== null ? e(money($r['quoted_price'])) : '–' ?></b>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">No bookings yet.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="g2">
    <section class="card">
      <h2>Vehicles</h2>
      <?php if ($vehicles): ?>
        <div class="lst">
          <?php foreach ($vehicles as $v): ?>
            <div class="row"><div class="g1"><b><?= e($v['make_model']) ?></b><span class="m"><?= e($v['plate_no']) ?><?= $v['colour'] ? ' · ' . e($v['colour']) : '' ?></span></div><span class="pill"><?= e(ucfirst($v['vehicle_type'])) ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">No vehicles added.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
