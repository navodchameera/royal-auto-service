<?php
defined('APP_PATH') or exit;
$title     = 'My jobs';
$navActive = 'jobs';
$base      = BASE_URL . '/index.php?url=';
$url       = $base . 'my-jobs&filter=' . $filter;
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>My jobs</h1>
    <div class="sub">Every booking assigned to you.</div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter jobs">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>my-jobs&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php foreach ($jobs as $j) { require APP_PATH . '/views/cleaner/_job.php'; } ?>
<?php if (!$jobs): ?><section class="card"><p class="empty">No jobs here yet.</p></section><?php endif; ?>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
