<?php
defined('APP_PATH') or exit;
$title     = 'My jobs';
$navActive = 'home';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';

// One small POST button for a job
$act = function (string $name, string $label, string $class, int $id, array $confirm = []) use ($base, $csrf) {
    return '<form method="post" action="' . e($base) . 'home"' . ($confirm ? Ui::confirm($confirm[0], $confirm[1], $confirm[2]) : '') . '>'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<input type="hidden" name="action" value="' . e($name) . '">'
        . '<input type="hidden" name="booking_id" value="' . $id . '">'
        . '<button class="btn ' . e($class) . '" type="submit">' . e($label) . '</button></form>';
};

$card = function (array $j) use ($act) {
    $id = (int)$j['booking_id'];
    ob_start(); ?>
    <section class="card">
      <div class="bk">
        <div class="g1">
          <h2 style="margin:0 0 4px"><?= e(substr($j['appointment_time'], 0, 5)) ?> · <?= e($j['make_model']) ?> <span class="m" style="font-weight:400"><?= e($j['plate_no']) ?></span></h2>
          <div class="m"><?= e(date('D j M Y', strtotime($j['appointment_date']))) ?> · <?= e($j['package_name']) ?><?= $j['quoted_price'] !== null ? ' · ' . e(money($j['quoted_price'])) : '' ?></div>
          <div class="m"><?= e(location_label($j['location_type'])) ?><?= $j['job_address'] ? ' · ' . e($j['job_address']) : '' ?></div>
          <div class="m">Customer: <?= e($j['full_name']) ?> · <a href="tel:<?= e($j['phone']) ?>"><?= e($j['phone']) ?></a></div>
          <p style="margin:12px 0 0">
            <span class="pill<?= $j['status'] === 'in_progress' ? ' ink' : '' ?>"><?= e(status_label($j['status'])) ?></span>
            <?php if ($j['pay_method']): ?><span class="pill">Paid · <?= e(method_label($j['pay_method'])) ?></span><?php endif; ?>
          </p>
        </div>
        <div class="acts">
          <?php if ($j['status'] === 'assigned'): ?>
            <?= $act('start', 'Start job', '', $id) ?>
          <?php elseif ($j['status'] === 'in_progress'): ?>
            <?= $act('complete', 'Complete job', '', $id, ['Complete job #' . $id . '?', 'Mark this job as completed. After that you can mark it as paid.', 'Complete job']) ?>
          <?php elseif ($j['status'] === 'completed' && !$j['pay_method'] && $j['quoted_price'] !== null): ?>
            <?= $act('pay', 'Mark as paid (cash)', '', $id, ['Mark job #' . $id . ' as paid?', 'Confirm that the customer paid ' . money($j['quoted_price']) . ' in cash.', 'Mark as paid']) ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php return ob_get_clean();
};
?>
<div class="top">
  <div>
    <h1>My jobs</h1>
    <div class="sub">Hello, <?= e(explode(' ', trim($user['name']))[0]) ?> · <?= e(date('l j F')) ?></div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<h2 class="sect">Upcoming</h2>
<?php foreach ($active as $j) { echo $card($j); } ?>
<?php if (!$active): ?><section class="card"><p class="empty">No jobs assigned to you right now.</p></section><?php endif; ?>

<h2 class="sect">Completed</h2>
<?php foreach ($done as $j) { echo $card($j); } ?>
<?php if (!$done): ?><section class="card"><p class="empty">No completed jobs yet.</p></section><?php endif; ?>

<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
