<?php
defined('APP_PATH') or exit;
$title     = 'Reviews';
$navActive = 'reviews';
$base      = BASE_URL . '/index.php?url=';
$stars     = function (int $n) { return str_repeat('★', $n) . str_repeat('☆', 5 - $n); };
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Reviews</h1>
    <div class="sub">What customers said after their service.</div>
  </div>
</div>

<div class="g2" style="grid-template-columns:1fr 2fr">
  <section class="card dk">
    <div class="m">Average rating</div>
    <div class="big"><?= $stats['n'] ? e(number_format($stats['avg'], 1)) : '–' ?></div>
    <div class="m"><?= (int)$stats['n'] ?> review<?= $stats['n'] === 1 ? '' : 's' ?></div>
  </section>
  <section class="card">
    <h2>Ratings</h2>
    <?php for ($i = 5; $i >= 1; $i--): $n = $stats['dist'][$i]; $pct = $stats['n'] ? round($n / $stats['n'] * 100) : 0; ?>
      <div class="row" style="padding:6px 0;border:0">
        <span style="width:44px"><?= $i ?> ★</span>
        <div class="g1" style="background:#EAF1F5;border-radius:999px;height:10px;overflow:hidden"><div style="width:<?= $pct ?>%;height:100%;background:#0A7C91"></div></div>
        <span class="m" style="width:32px;text-align:right"><?= $n ?></span>
      </div>
    <?php endfor; ?>
  </section>
</div>

<nav class="f" aria-label="Filter by rating">
  <a href="<?= e($base) ?>manage-reviews"<?= $rating === 0 ? ' class="on" aria-current="page"' : '' ?>>All</a>
  <?php for ($i = 5; $i >= 1; $i--): ?>
    <a href="<?= e($base) ?>manage-reviews&amp;rating=<?= $i ?>"<?= $rating === $i ? ' class="on" aria-current="page"' : '' ?>><?= $i ?> ★</a>
  <?php endfor; ?>
</nav>

<section class="card">
  <?php if ($reviews): ?>
    <div class="lst">
      <?php foreach ($reviews as $r): $n = (int)$r['rating']; ?>
        <div class="row" style="align-items:flex-start">
          <div class="g1">
            <span class="st" role="img" aria-label="<?= $n ?> out of 5 stars"><?= e($stars($n)) ?></span>
            <b><?= e($r['full_name']) ?> · <?= e($r['package_name']) ?></b>
            <span class="m"><?= e(date('j M Y', strtotime($r['review_date']))) ?><?= $r['cleaner_name'] ? ' · Cleaner: ' . e($r['cleaner_name']) : '' ?></span>
            <?php if ($r['comment']): ?><span style="display:block;margin-top:6px"><?= nl2br(e($r['comment'])) ?></span><?php endif; ?>
          </div>
          <a class="btn g" href="<?= e($base) ?>manage-bookings&amp;id=<?= (int)$r['booking_id'] ?>">Booking #<?= (int)$r['booking_id'] ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="empty">No reviews here yet.</p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
