<?php
defined('APP_PATH') or exit;
$title     = 'My bookings';
$navActive = 'bookings';
$base      = BASE_URL . '/index.php?url=';
$cancellable = ['requested', 'quoted', 'accepted', 'assigned'];
require APP_PATH . '/views/layout/app_header.php';

// Small helper: a POST button for one booking
$action = function (string $name, string $label, string $class, int $id, string $confirm = '') use ($base, $filter, $csrf) {
    return '<form method="post" action="' . e($base) . 'bookings&amp;filter=' . e($filter) . '"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<input type="hidden" name="action" value="' . e($name) . '">'
        . '<input type="hidden" name="booking_id" value="' . $id . '">'
        . '<button class="btn ' . e($class) . '" type="submit">' . e($label) . '</button></form>';
};
?>
<div class="top">
  <div>
    <h1>My bookings</h1>
    <div class="sub">Accept a quotation to confirm your booking.</div>
  </div>
  <a class="btn" href="<?= e($base) ?>book">Book a service</a>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter bookings">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>bookings&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$bookings): ?>
  <section class="card"><p class="empty">No bookings here yet.</p></section>
<?php endif; ?>

<?php foreach ($bookings as $b): $id = (int)$b['booking_id']; $quoted = $b['status'] === 'quoted'; ?>
  <section class="card">
    <div class="bk">
      <div class="g1">
        <h2 style="margin:0 0 4px">#<?= $id ?> · <?= e($b['make_model']) ?> <span class="m" style="font-weight:400"><?= e($b['plate_no']) ?></span></h2>
        <div class="m"><?= e(date('D j M Y, H:i', strtotime($b['appointment_date'] . ' ' . $b['appointment_time']))) ?> · <?= e($b['package_name']) ?></div>
        <div class="m"><?= e(location_label($b['location_type'])) ?><?= $b['job_address'] ? ' · ' . e($b['job_address']) : '' ?> · <?= (int)$b['photo_count'] ?> photo<?= (int)$b['photo_count'] === 1 ? '' : 's' ?></div>
        <p style="margin:12px 0 0"><span class="pill<?= $quoted ? ' ink' : '' ?>"><?= e(status_label($b['status'])) ?></span></p>
        <?php if (in_array($b['status'], $cancellable, true) && !$quoted): ?>
          <div class="acts" style="margin-top:14px"><?= $action('cancel', 'Cancel booking', 'g', $id, 'Cancel booking #' . $id . '?') ?></div>
        <?php endif; ?>
      </div>

      <?php if ($quoted): ?>
        <div class="qbox">
          <div class="m">Quotation</div>
          <div class="qprice"><?= e(money($b['quoted_price'])) ?></div>
          <?php if ($b['condition_level']): ?><p style="margin:0 0 6px"><span class="pill">Condition: <?= e(ucfirst($b['condition_level'])) ?></span></p><?php endif; ?>
          <?php if ($b['remarks']): ?><p class="m" style="margin:0 0 10px"><?= nl2br(e($b['remarks'])) ?></p><?php endif; ?>
          <div class="acts">
            <?= $action('accept', 'Accept quotation', '', $id) ?>
            <?= $action('reject', 'Reject', 'g', $id, 'Reject this quotation? Booking #' . $id . ' will be cancelled.') ?>
          </div>
        </div>
      <?php elseif ($b['quoted_price'] !== null && $b['status'] !== 'cancelled'): ?>
        <div class="qbox"><div class="m">Price</div><div class="qprice"><?= e(money($b['quoted_price'])) ?></div></div>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<script src="<?= BASE_URL ?>/assets/js/confirm.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
