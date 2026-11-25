<?php
defined('APP_PATH') or exit;
$title     = 'Dashboard';
$navActive = 'home';
require APP_PATH . '/views/layout/app_header.php';

$tiles = [
    ['New requests to quote',      $counts['requests'],     'Review requests', true,  BASE_URL . '/index.php?url=manage-quotations'],
    ['Waiting for customer reply', $counts['waiting'],      'View quotations', false, BASE_URL . '/index.php?url=manage-quotations&filter=quoted'],
    ['Jobs today',                 count($schedule),        'See bookings',    false, BASE_URL . '/index.php?url=manage-bookings'],
];
?>
<div class="top">
  <div>
    <h1>Today at Royal Auto</h1>
    <div class="sub"><?= e(date('l j F')) ?> · what needs you first.</div>
  </div>
</div>

<div class="g2" style="grid-template-columns:repeat(3,1fr)">
  <?php foreach ($tiles as [$label, $count, $button, $dark, $href]): ?>
    <section class="card<?= $dark ? ' dk' : '' ?>">
      <div class="m"><?= e($label) ?></div>
      <div class="big"><?= (int)$count ?></div>
      <a class="btn<?= $dark ? ' a' : '' ?>" href="<?= e($href) ?>"><?= e($button) ?></a>
    </section>
  <?php endforeach; ?>
</div>

<div class="g2" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <section class="card">
    <h2>Today’s schedule</h2>
    <?php if ($schedule): ?>
      <div class="lst">
        <?php foreach ($schedule as $s):
            $needs = $s['status'] === 'accepted' && $s['cleaner_name'] === null;
        ?>
          <div class="row">
            <div class="tm"><?= e(substr($s['appointment_time'], 0, 5)) ?></div>
            <div class="g1">
              <b><?= e($s['make_model']) ?> · <?= e($s['package_name']) ?></b>
              <span class="m"><?= e(location_label($s['location_type'])) ?> · <?= $s['cleaner_name'] !== null ? e($s['cleaner_name']) : 'no cleaner yet' ?></span>
            </div>
            <span class="pill<?= $needs ? ' ink' : '' ?>"><?= $needs ? 'Needs cleaner' : e(status_label($s['status'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">No jobs are scheduled for today.</p>
    <?php endif; ?>
  </section>

  <div class="g2">
    <section class="card">
      <h2>Cleaners today</h2>
      <?php if ($cleaners): ?>
        <div class="lst">
          <?php foreach ($cleaners as $c): $jobs = (int)$c['jobs_today']; $busy = (int)$c['busy'] > 0; ?>
            <div class="row">
              <div class="g1"><b><?= e($c['full_name']) ?></b><span class="m"><?= $jobs === 0 ? 'No jobs today' : $jobs . ' job' . ($jobs === 1 ? '' : 's') . ' today' ?></span></div>
              <span class="pill<?= $busy ? ' ink' : '' ?>"><?= $busy ? 'On a job' : 'Free now' ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">No cleaner accounts yet.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Latest reviews</h2>
      <?php if ($reviews): ?>
        <div class="lst">
          <?php foreach ($reviews as $r): $n = (int)$r['rating']; ?>
            <div class="row">
              <div class="g1">
                <span class="st" role="img" aria-label="<?= $n ?> out of 5 stars"><?= str_repeat('★', $n) . str_repeat('☆', 5 - $n) ?></span>
                <b>Booking #<?= (int)$r['booking_id'] ?> · <?= e($r['full_name']) ?></b>
                <?php if ($r['comment']): ?><span class="m"><?= e($r['comment']) ?></span><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">No reviews yet.</p>
      <?php endif; ?>
      <a class="btn g" href="#" style="margin-top:14px">View all reviews</a>
    </section>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
