<?php
defined('APP_PATH') or exit;
$title     = 'Dashboard';
$navActive = 'home';
$url       = BASE_URL . '/index.php?url=home';
$first     = explode(' ', trim($user['name']))[0];
require APP_PATH . '/views/layout/app_header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cleaner.css">
<div class="top">
  <div>
    <h1>Hello, <?= e($first) ?></h1>
    <div class="sub"><?= e(date('l j F')) ?> · <?= count($todayJobs) ?> job<?= count($todayJobs) === 1 ? '' : 's' ?> today</div>
  </div>
  <a class="btn g" href="<?= BASE_URL ?>/index.php?url=my-jobs">All my jobs</a>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:repeat(3,1fr)">
  <section class="card dk"><div class="m">Today’s jobs</div><div class="big sm"><?= count($todayJobs) ?></div></section>
  <section class="card"><div class="m">Cash or payment to collect</div><div class="big sm"><?= (int)$toCollect ?></div></section>
  <section class="card"><div class="m">Jobs on later days</div><div class="big sm"><?= (int)$laterCount ?></div></section>
</div>

<h2 class="sect">Today’s jobs</h2>
<?php foreach ($todayJobs as $j) { require APP_PATH . '/views/cleaner/_job.php'; } ?>

<?php if (!$todayJobs): ?>
  <section class="card">
    <p class="empty" style="margin-bottom:<?= $next ? '10px' : '0' ?>">You have no jobs today.</p>
    <?php if ($next): ?>
      <p style="margin:0">Next job: <b><?= e(date('D j M', strtotime($next['appointment_date']))) ?>, <?= e(substr($next['appointment_time'], 0, 5)) ?></b>
        · <?= e($next['make_model']) ?> · <a href="<?= BASE_URL ?>/index.php?url=my-jobs&amp;filter=active">see all my jobs</a></p>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
