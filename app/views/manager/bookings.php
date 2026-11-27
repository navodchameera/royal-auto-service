<?php
defined('APP_PATH') or exit;
$title     = 'Bookings';
$navActive = 'bookings';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Bookings</h1>
    <div class="sub">Every request, quotation and job in one place.</div>
  </div>
  <form class="search" method="get" action="<?= BASE_URL ?>/index.php" role="search">
    <input type="hidden" name="url" value="manage-bookings">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input class="in" type="search" name="q" value="<?= e($q) ?>" placeholder="Search customer, plate or #" aria-label="Search bookings">
    <button class="btn" type="submit">Search</button>
  </form>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter by status">
  <?php foreach ($statuses as $key => $label): ?>
    <a href="<?= e($base) ?>manage-bookings&amp;status=<?= e($key) ?>&amp;q=<?= e(urlencode($q)) ?>"<?= $key === $status ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card">
  <?php if ($bookings): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Package</th><th>When</th><th>Status</th><th>Payment</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): $id = (int)$b['booking_id']; ?>
          <tr>
            <td><b>#<?= $id ?></b></td>
            <td><?= e($b['full_name']) ?></td>
            <td><?= e($b['make_model']) ?><br><span class="m"><?= e($b['plate_no']) ?></span></td>
            <td><?= e($b['package_name']) ?><br><span class="m"><?= e(location_label($b['location_type'])) ?></span></td>
            <td><?= e(date('j M Y', strtotime($b['appointment_date']))) ?><br><span class="m"><?= e(substr($b['appointment_time'], 0, 5)) ?></span></td>
            <td><span class="pill<?= $b['status'] === 'requested' ? ' ink' : '' ?>"><?= e(status_label($b['status'])) ?></span></td>
            <td><?php if ($b['pay_method']): ?><span class="pill">Paid · <?= e(method_label($b['pay_method'])) ?></span><?php elseif ($b['status'] === 'completed'): ?><span class="pill ink">Unpaid</span><?php else: ?><span class="m">–</span><?php endif; ?></td>
            <td>
              <?php if ($b['status'] === 'requested'): ?>
                <a class="btn" href="<?= e($base) ?>manage-quotations&amp;id=<?= $id ?>">Quote</a>
              <?php else: ?>
                <a class="btn g" href="<?= e($base) ?>manage-bookings&amp;id=<?= $id ?>">Open</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty">No bookings match this filter.</p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
