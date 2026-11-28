<?php
defined('APP_PATH') or exit;
// One job in full. Expects: $j (job + booking details), $photos, $slots, $csrf, $error, $flash
$title     = 'Job #' . (int)$j['booking_id'];
$navActive = 'jobs';
$base      = BASE_URL . '/index.php?url=';
$id        = (int)$j['booking_id'];
$url       = $base . 'my-jobs&id=' . $id;
$st        = $j['status'];
$paid      = (bool)$j['pay_method'];
$price     = $j['quoted_price'];
$working   = in_array($st, ['assigned', 'in_progress'], true);
$site      = $j['location_type'] === 'customer_site';
$labels = [
    'assigned' => 'Cleaner assigned', 'in_progress' => 'In progress',
    'completed' => 'Done · waiting for the customer', 'finished' => 'Completed', 'cancelled' => 'Cancelled',
];
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <a class="back" href="<?= e($base) ?>my-jobs">← My jobs</a>
    <h1>Job #<?= $id ?></h1>
    <div class="sub">
      <span class="pill<?= $st === 'in_progress' ? ' ink' : '' ?>"><?= e($labels[$st] ?? status_label($st)) ?></span>
      <?php if ($paid): ?><span class="pill">Paid · <?= e(method_label($j['pay_method'])) ?></span>
      <?php elseif ($price !== null && $st !== 'cancelled'): ?><span class="pill ink">Unpaid</span><?php endif; ?>
    </div>
  </div>
  <div class="acts">
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

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($working && !$paid && $price !== null): ?>
  <div class="alert note">The customer has not paid yet. You can take <?= e(money($price)) ?> in cash and mark it as paid. The job can be completed once it is paid.</div>
<?php endif; ?>

<div class="g2" style="grid-template-columns:1fr 1fr;align-items:start">
  <div class="g2">
    <section class="card">
      <h2>Appointment</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Date</span><b><?= e(date('D j M Y', strtotime($j['appointment_date']))) ?></b></div>
        <div class="row"><span class="m g1">Time</span><b><?= e(substr($j['appointment_time'], 0, 5)) ?></b></div>
        <div class="row"><span class="m g1">Where</span><b><?= e(location_label($j['location_type'])) ?></b></div>
        <?php if ($site && $j['job_address']): ?><div class="row"><span class="m g1">Job address</span><b><?= e($j['job_address']) ?></b></div><?php endif; ?>
        <div class="row"><span class="m g1">Package</span><b><?= e($j['package_name']) ?></b></div>
        <?php if ($j['package_desc']): ?><div class="row"><span class="m g1">Includes</span><b><?= e($j['package_desc']) ?></b></div><?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2>Vehicle</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Vehicle</span><b><?= e($j['make_model']) ?></b></div>
        <div class="row"><span class="m g1">Plate</span><b><?= e($j['plate_no']) ?></b></div>
        <div class="row"><span class="m g1">Type</span><b><?= e(ucfirst($j['vehicle_type'])) ?></b></div>
        <?php if ($j['colour']): ?><div class="row"><span class="m g1">Colour</span><b><?= e($j['colour']) ?></b></div><?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2>Customer</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Name</span><b><?= e($j['full_name']) ?></b></div>
        <div class="row"><span class="m g1">Phone</span><b><a href="tel:<?= e($j['phone']) ?>"><?= e($j['phone']) ?></a></b></div>
        <div class="row"><span class="m g1">Email</span><b><a href="mailto:<?= e($j['email']) ?>"><?= e($j['email']) ?></a></b></div>
        <div class="row"><span class="m g1">Address</span><b><?= e($j['cust_address']) ?></b></div>
      </div>
    </section>
  </div>

  <div class="g2">
    <section class="card">
      <h2>Price and payment</h2>
      <?php if ($price !== null): ?>
        <div class="qprice"><?= e(money($price)) ?></div>
        <p style="margin:0 0 8px">
          <?php if ($j['condition_level']): ?><span class="pill">Condition: <?= e(ucfirst($j['condition_level'])) ?></span><?php endif; ?>
          <?php if ($paid): ?><span class="pill">Paid · <?= e(method_label($j['pay_method'])) ?></span><?php else: ?><span class="pill ink">Unpaid</span><?php endif; ?>
        </p>
        <?php if ($j['remarks']): ?><p class="m" style="margin:0"><?= nl2br(e($j['remarks'])) ?></p><?php endif; ?>
      <?php else: ?>
        <p class="empty">No price has been set yet.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Progress</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Booked</span><b><?= e(when_label($j['created_at'])) ?></b></div>
        <div class="row"><span class="m g1">Quotation sent</span><b><?= e(when_label($j['quotation_date'])) ?></b></div>
        <div class="row"><span class="m g1">Job started</span><b><?= e(when_label($j['arrived_at'])) ?></b></div>
        <div class="row"><span class="m g1">Paid</span><b><?= e(when_label($j['pay_date'])) ?></b></div>
        <div class="row"><span class="m g1">Job completed</span><b><?= e(when_label($j['completed_at'])) ?></b></div>
      </div>
    </section>

    <section class="card">
      <h2>Photos from the customer</h2>
      <?php if ($photos): ?>
        <div class="photos">
          <?php foreach ($photos as $p): $label = $slots[$p['photo_area']] ?? 'Photo'; ?>
            <a href="<?= BASE_URL ?>/uploads/booking_photos/<?= e($p['file_path']) ?>" target="_blank" rel="noopener">
              <img src="<?= BASE_URL ?>/uploads/booking_photos/<?= e($p['file_path']) ?>" alt="<?= e($label) ?> photo" loading="lazy">
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">No photos were uploaded.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
