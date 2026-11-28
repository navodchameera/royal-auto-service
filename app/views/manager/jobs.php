<?php
defined('APP_PATH') or exit;
$title     = 'Jobs';
$navActive = 'jobs';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
$labels = ['assigned' => 'Assigned', 'in_progress' => 'In progress', 'completed' => 'Done · waiting for customer', 'finished' => 'Completed', 'cancelled' => 'Cancelled'];
?>
<div class="top">
  <div>
    <h1>Jobs</h1>
    <div class="sub">Every job, the cleaner doing it, and when it started and finished.</div>
  </div>
</div>

<nav class="f" aria-label="Filter jobs">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>manage-jobs&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card">
  <?php if ($jobs): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Booking</th><th>When</th><th>Vehicle</th><th>Cleaner</th><th>Status</th><th>Started</th><th>Finished</th><th>Payment</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j): $id = (int)$j['booking_id']; ?>
          <tr>
            <td><b>#<?= $id ?></b><span class="m"><?= e($j['customer_name']) ?></span></td>
            <td><?= e(date('j M Y', strtotime($j['appointment_date']))) ?><br><span class="m"><?= e(substr($j['appointment_time'], 0, 5)) ?></span></td>
            <td><?= e($j['make_model']) ?><br><span class="m"><?= e($j['plate_no']) ?> · <?= e($j['package_name']) ?></span></td>
            <td><?= e($j['cleaner_name']) ?></td>
            <td><span class="pill<?= $j['status'] === 'in_progress' ? ' ink' : '' ?>"><?= e($labels[$j['status']] ?? status_label($j['status'])) ?></span></td>
            <td><?= $j['arrived_at'] ? e(date('j M, H:i', strtotime($j['arrived_at']))) : '–' ?></td>
            <td><?= $j['completed_at'] ? e(date('j M, H:i', strtotime($j['completed_at']))) : '–' ?></td>
            <td><?= $j['pay_method'] ? '<span class="pill">Paid · ' . e(method_label($j['pay_method'])) . '</span>' : '<span class="m">–</span>' ?></td>
            <td><a class="btn g" href="<?= e($base) ?>manage-bookings&amp;id=<?= $id ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty">No jobs match this filter.</p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
