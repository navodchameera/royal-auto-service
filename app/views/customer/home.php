<?php
defined('APP_PATH') or exit;
$title     = 'Home';
$navActive = 'home';
$first     = explode(' ', trim($user['name']))[0];
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Hello, <?= e($first) ?></h1>
    <div class="sub"><?= e(date('l j F')) ?> · here is what is happening with your vehicles.</div>
  </div>
  <a class="btn" href="#">Book a service</a>
</div>

<div class="g2" style="grid-template-columns:1.35fr 1fr">
  <?php if ($next): ?>
  <section class="card dk">
    <span class="pill">Next appointment</span>
    <div class="when"><?= e(date('D j M, H:i', strtotime($next['appointment_date'] . ' ' . $next['appointment_time']))) ?></div>
    <div class="m big-m"><?= e($next['make_model']) ?> · <?= e($next['plate_no']) ?></div>
    <div class="tags">
      <span class="pill"><?= e($next['package_name']) ?></span>
      <span class="pill"><?= e(location_label($next['location_type'])) ?></span>
      <span class="pill"><?= e(status_label($next['status'])) ?></span>
    </div>
    <a class="btn a" href="#">View booking</a>
  </section>
  <?php else: ?>
  <section class="card dk">
    <span class="pill">Next appointment</span>
    <div class="when">Nothing booked yet</div>
    <div class="m big-m">Book a wash or detailing and it will show up here.</div>
    <a class="btn a" href="#" style="margin-top:24px">Book a service</a>
  </section>
  <?php endif; ?>

  <section class="card">
    <h2>Needs your attention</h2>
    <?php if ($attention): ?>
      <div class="lst">
        <?php foreach ($attention as $item): ?>
          <div class="row">
            <div class="g1"><b><?= e($item['title']) ?></b><span class="m"><?= e($item['meta']) ?></span></div>
            <a class="btn g" href="#"><?= e($item['button']) ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">You are all caught up.</p>
    <?php endif; ?>
  </section>
</div>

<div class="g2" style="grid-template-columns:1fr 1.35fr">
  <section class="card">
    <h2>My vehicles</h2>
    <?php if ($vehicles): ?>
      <div class="lst">
        <?php foreach ($vehicles as $v): ?>
          <div class="row">
            <div class="g1"><b><?= e($v['make_model']) ?></b><span class="m"><?= e($v['plate_no']) ?></span></div>
            <a class="btn g" href="#">Book</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">No vehicles yet. Add one to start booking.</p>
    <?php endif; ?>
    <a class="btn g" href="#" style="margin-top:16px">Add a vehicle</a>
  </section>

  <section class="card">
    <h2>Recent bookings</h2>
    <?php if ($recent): ?>
      <div class="lst">
        <?php foreach ($recent as $r): ?>
          <div class="row">
            <div class="g1">
              <b>#<?= (int)$r['booking_id'] ?> · <?= e($r['make_model']) ?></b>
              <span class="m"><?= e(date('D j M', strtotime($r['appointment_date']))) ?> · <?= e($r['package_name']) ?></span>
            </div>
            <span class="pill<?= $r['status'] === 'quoted' ? ' ink' : '' ?>"><?= e(status_label($r['status'])) ?></span>
            <b class="price"><?= $r['quoted_price'] !== null ? e(money($r['quoted_price'])) : '–' ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">Your bookings will be listed here.</p>
    <?php endif; ?>
  </section>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
