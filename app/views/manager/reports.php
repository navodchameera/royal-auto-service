<?php
defined('APP_PATH') or exit;
// Expects: $type, $period, $report, $customFrom, $customTo
$title     = 'Reports';
$navActive = 'reports';
$base      = BASE_URL . '/index.php?url=manage-reports';
$q         = function (array $extra = []) use ($base, $type, $period, $customFrom, $customTo) {
    $args = array_merge(['report' => $type, 'period' => $period['key']], $period['key'] === 'custom' ? ['from' => $customFrom, 'to' => $customTo] : [], $extra);
    return $base . '&' . http_build_query($args, '', '&amp;');
};
$right = ['money', 'int', 'pct', 'dec'];
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top noprint">
  <div>
    <h1>Reports</h1>
    <div class="sub">See how the business is doing and where to act. Pick a report and a period, then read, print or download it.</div>
  </div>
  <div class="acts">
    <a class="btn" href="<?= $q(['download' => 'pdf']) ?>">Download PDF</a>
    <a class="btn g" href="<?= $q(['download' => 'csv']) ?>">Download CSV</a>
    <button class="btn g" type="button" onclick="window.print()">Print</button>
  </div>
</div>

<nav class="f noprint" aria-label="Choose a report">
  <?php foreach (Reports::TYPES as $key => $label): ?>
    <a href="<?= e($base) ?>&amp;report=<?= e($key) ?>&amp;period=<?= e($period['key']) ?><?= $period['key'] === 'custom' ? '&amp;from=' . e(urlencode($customFrom)) . '&amp;to=' . e(urlencode($customTo)) : '' ?>"<?= $key === $type ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card noprint">
  <div class="top" style="margin:0;align-items:center">
    <nav class="f" aria-label="Choose a period">
      <?php foreach (Reports::PERIODS as $key => $label): if ($key === 'custom') { continue; } ?>
        <a href="<?= e($base) ?>&amp;report=<?= e($type) ?>&amp;period=<?= e($key) ?>"<?= $key === $period['key'] ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <form class="search" method="get" action="<?= BASE_URL ?>/index.php">
      <input type="hidden" name="url" value="manage-reports">
      <input type="hidden" name="report" value="<?= e($type) ?>">
      <input type="hidden" name="period" value="custom">
      <input class="in" type="date" name="from" value="<?= e($customFrom) ?>" aria-label="From date" style="width:auto">
      <input class="in" type="date" name="to" value="<?= e($customTo) ?>" aria-label="To date" style="width:auto">
      <button class="btn<?= $period['key'] === 'custom' ? '' : ' g' ?>" type="submit">Show period</button>
    </form>
  </div>
</section>

<?php if ($period['error'] !== ''): ?><div class="alert noprint" role="alert"><?= e($period['error']) ?> Showing the last 30 days instead.</div><?php endif; ?>

<section class="card rhead">
  <h2 style="margin:0 0 4px;font-size:20px"><?= e($report['title']) ?></h2>
  <div class="m"><?= e($period['label']) ?> · <?= e($report['basis']) ?></div>
</section>

<div class="kpis">
  <?php foreach ($report['kpis'] as $i => [$label, $value, $kt, $note]): ?>
    <section class="card<?= $i === 0 ? ' dk' : '' ?>">
      <div class="m"><?= e($label) ?></div>
      <div class="big sm"><?= e(Reports::fmt($value, $kt)) ?></div>
      <?php if ($note !== ''): ?><div class="m"><?= e($note) ?></div><?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<section class="card">
  <h2>What this means</h2>
  <ul class="ins">
    <?php foreach ($report['insights'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
  </ul>
</section>

<?php foreach ($report['sections'] as $sec):
    $max = 0;
    if ($sec['bar'] !== null) { foreach ($sec['rows'] as $row) { $max = max($max, (float)($row[$sec['bar']] ?? 0)); } }
?>
  <section class="card">
    <h2><?= e($sec['title']) ?></h2>
    <?php if (!empty($sec['note'])): ?><p class="m" style="margin:-8px 0 12px"><?= e($sec['note']) ?></p><?php endif; ?>
    <?php if ($sec['rows']): ?>
      <div class="tblwrap">
        <table class="tbl">
          <thead><tr>
            <?php foreach ($sec['cols'] as [$cl, $ct]): ?><th<?= in_array($ct, $right, true) ? ' class="num"' : '' ?>><?= e($cl) ?></th><?php endforeach; ?>
          </tr></thead>
          <tbody>
          <?php foreach ($sec['rows'] as $row): ?>
            <tr>
              <?php foreach ($sec['cols'] as $i => [$cl, $ct]): $isNum = in_array($ct, $right, true); ?>
                <td<?= $isNum ? ' class="num"' : '' ?>>
                  <?= e(Reports::fmt($row[$i] ?? null, $ct)) ?>
                  <?php if ($sec['bar'] === $i && $max > 0): ?><span class="bar" aria-hidden="true"><i style="width:<?= round((float)$row[$i] / $max * 100) ?>%"></i></span><?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="empty">No data in this period.</p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
