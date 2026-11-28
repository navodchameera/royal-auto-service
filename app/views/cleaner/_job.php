<?php
defined('APP_PATH') or exit;
// One job card. Expects: $j (job row), $url (where the buttons post), $csrf
$id     = (int)$j['booking_id'];
$st     = $j['status'];
$paid   = (bool)$j['pay_method'];
$price  = $j['quoted_price'];
$labels = [
    'assigned'    => 'Cleaner assigned',
    'in_progress' => 'In progress',
    'completed'   => 'Done · waiting for the customer',
    'finished'    => 'Completed',
    'cancelled'   => 'Cancelled',
];
$stage   = $labels[$st] ?? status_label($st);
$working = in_array($st, ['assigned', 'in_progress'], true);
$site    = $j['location_type'] === 'customer_site';
?>
<section class="card">
  <div class="bk">
    <div class="g1">
      <h2 style="margin:0 0 4px"><?= e(substr($j['appointment_time'], 0, 5)) ?> · <?= e($j['make_model']) ?> <span class="m" style="font-weight:400"><?= e($j['plate_no']) ?></span></h2>
      <div class="m"><?= e(date('D j M Y', strtotime($j['appointment_date']))) ?> · <?= e($j['package_name']) ?><?= $price !== null ? ' · ' . e(money($price)) : '' ?></div>
      <div class="m"><?= e(location_label($j['location_type'])) ?><?php if ($site && $j['job_address']): ?> · <?= e($j['job_address']) ?><?php endif; ?></div>
      <div class="m">Customer: <?= e($j['full_name']) ?> · <a href="tel:<?= e($j['phone']) ?>"><?= e($j['phone']) ?></a></div>

      <p style="margin:12px 0 0">
        <span class="pill<?= $st === 'in_progress' ? ' ink' : '' ?>"><?= e($stage) ?></span>
        <?php if ($paid): ?>
          <span class="pill">Paid · <?= e(method_label($j['pay_method'])) ?></span>
        <?php elseif ($price !== null && $st !== 'cancelled'): ?>
          <span class="pill ink">Unpaid</span>
        <?php endif; ?>
      </p>

      <?php if ($working && !$paid && $price !== null): ?>
        <p class="m" style="margin:10px 0 0">The customer has not paid yet. You can take <?= e(money($price)) ?> in cash and mark it as paid. The job can be completed once it is paid.</p>
      <?php elseif ($st === 'in_progress' && $paid): ?>
        <p class="m" style="margin:10px 0 0">Paid. You can complete the job.</p>
      <?php elseif ($st === 'completed'): ?>
        <p class="m" style="margin:10px 0 0">The customer will rate the service and confirm that the job is complete.</p>
      <?php endif; ?>
    </div>

    <div class="acts">
      <a class="btn g" href="<?= BASE_URL ?>/index.php?url=my-jobs&amp;id=<?= $id ?>">View details</a>
      <?php if ($st === 'assigned'): ?>
        <?= Ui::postButton($url, $csrf, ['action' => 'start', 'booking_id' => $id], 'Start job') ?>
      <?php endif; ?>

      <?php if ($working && !$paid && $price !== null): ?>
        <?= Ui::postButton($url, $csrf, ['action' => 'pay', 'booking_id' => $id], 'Mark as paid (cash)', $st === 'in_progress' ? '' : 'g',
              ['Mark job #' . $id . ' as paid?', 'Confirm that the customer paid ' . money($price) . ' in cash. This is recorded as a cash payment.', 'Mark as paid']) ?>
      <?php endif; ?>

      <?php if ($st === 'in_progress' && $paid): ?>
        <?= Ui::postButton($url, $csrf, ['action' => 'complete', 'booking_id' => $id], 'Complete job', '',
              ['Complete job #' . $id . '?', 'Mark the work as done. The customer will then rate the service and confirm.', 'Complete job']) ?>
      <?php endif; ?>
    </div>
  </div>
</section>
