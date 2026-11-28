<?php
defined('APP_PATH') or exit;
$title     = 'Payments';
$navActive = 'payments';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Payments</h1>
    <div class="sub">Online payments are demo card payments. Cash is recorded by the cleaner or by you.</div>
  </div>
</div>

<div class="g2" style="grid-template-columns:repeat(4,1fr)">
  <section class="card dk"><div class="m">Collected</div><div class="big sm"><?= e(money($totals['total'])) ?></div></section>
  <section class="card"><div class="m">Online</div><div class="big sm"><?= e(money($totals['online'])) ?></div></section>
  <section class="card"><div class="m">Cash</div><div class="big sm"><?= e(money($totals['cash'])) ?></div></section>
  <section class="card"><div class="m">Still unpaid</div><div class="big sm"><?= e(money($totals['unpaid'])) ?></div></section>
</div>

<nav class="f" aria-label="Filter payments">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>manage-payments&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card">
  <?php if ($rows): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Amount</th><th><?= $filter === 'unpaid' ? 'Appointment' : 'Method' ?></th><th><?= $filter === 'unpaid' ? 'Status' : 'Paid on' ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $id = (int)$r['booking_id']; ?>
          <tr>
            <td><b>#<?= $id ?></b></td>
            <td><?= e($r['full_name']) ?></td>
            <td><?= e($r['make_model']) ?></td>
            <td><b><?= e(money($r['amount'])) ?></b></td>
            <?php if ($filter === 'unpaid'): ?>
              <td><?= e(date('j M Y', strtotime($r['appointment_date']))) ?></td>
              <td><span class="pill ink">Unpaid · <?= e(status_label($r['status'])) ?></span></td>
            <?php else: ?>
              <td><span class="pill"><?= e(method_label((string)$r['method'])) ?></span></td>
              <td><?= e(when_label($r['payment_date'])) ?></td>
            <?php endif; ?>
            <td><a class="btn g" href="<?= e($base) ?>manage-bookings&amp;id=<?= $id ?>"><?= $filter === 'unpaid' ? 'Open / mark paid' : 'Open' ?></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty"><?= $filter === 'unpaid' ? 'Nothing is waiting for payment.' : 'No payments here yet.' ?></p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
