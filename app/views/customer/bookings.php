<?php
defined('APP_PATH') or exit;
$title     = 'My bookings';
$navActive = 'bookings';
$base      = BASE_URL . '/index.php?url=';
$cancellable = ['requested', 'quoted', 'accepted', 'assigned'];
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>My bookings</h1>
    <div class="sub">We confirm each booking with a price and a cleaner. You can cancel any time before the work starts.</div>
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

<?php foreach ($bookings as $b):
    $id        = (int)$b['booking_id'];
    $canCancel = in_array($b['status'], $cancellable, true);
    $hasQuote  = $b['quoted_price'] !== null && $b['status'] !== 'cancelled';
?>
  <section class="card">
    <div class="bk">
      <div class="g1">
        <h2 style="margin:0 0 4px">#<?= $id ?> · <?= e($b['make_model']) ?> <span class="m" style="font-weight:400"><?= e($b['plate_no']) ?></span></h2>
        <div class="m"><?= e(date('D j M Y, H:i', strtotime($b['appointment_date'] . ' ' . $b['appointment_time']))) ?> · <?= e($b['package_name']) ?></div>
        <div class="m"><?= e(location_label($b['location_type'])) ?><?= $b['job_address'] ? ' · ' . e($b['job_address']) : '' ?> · <?= (int)$b['photo_count'] ?> photo<?= (int)$b['photo_count'] === 1 ? '' : 's' ?></div>
        <p style="margin:12px 0 0"><span class="pill"><?= e(status_label($b['status'])) ?></span></p>

        <?php if ($canCancel): ?>
          <div class="acts" style="margin-top:14px">
            <form method="post" action="<?= e($base) ?>bookings&amp;filter=<?= e($filter) ?>"<?= Ui::confirm('Cancel booking #' . $id . '?', 'Your booking will be cancelled and the cleaner will be released for that time. You can book again whenever you like.', 'Cancel booking', 'danger', 'Keep booking') ?>>
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="booking_id" value="<?= $id ?>">
              <button class="btn g" type="submit">Cancel booking</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($hasQuote): ?>
        <div class="qbox">
          <div class="m">Quotation</div>
          <div class="qprice"><?= e(money($b['quoted_price'])) ?></div>
          <?php if ($b['condition_level']): ?><p style="margin:0 0 6px"><span class="pill">Condition: <?= e(ucfirst($b['condition_level'])) ?></span></p><?php endif; ?>
          <?php if ($b['remarks']): ?><p class="m" style="margin:0 0 6px"><?= nl2br(e($b['remarks'])) ?></p><?php endif; ?>
          <?php if ($b['status'] === 'assigned'): ?>
            <p class="m" style="margin:0">Your booking is confirmed at this price. If you do not want to go ahead, cancel it before the work starts.</p>
          <?php endif; ?>
          <?php if ($b['pay_method']): ?>
            <p style="margin:10px 0 0"><span class="pill">Paid · <?= e(method_label($b['pay_method'])) ?></span></p>
          <?php elseif (in_array($b['status'], ['assigned', 'in_progress', 'completed'], true)): ?>
            <div class="acts" style="margin-top:12px"><a class="btn" href="<?= e($base) ?>pay&amp;id=<?= $id ?>">Pay online</a></div>
            <p class="m" style="margin:8px 0 0">Or pay in cash after the service.</p>
          <?php endif; ?>
        </div>
      <?php elseif ($b['status'] === 'requested'): ?>
        <div class="qbox"><div class="m">We are reviewing your photos and will confirm your booking with a price and a cleaner.</div></div>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
