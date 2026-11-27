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
    <div class="sub">Pay online once your booking is confirmed, or pay in cash after the service.</div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:1fr 1fr">
  <section class="card dk"><div class="m">Still to pay</div><div class="big"><?= e(money($dueTotal)) ?></div><span class="m"><?= count($due) ?> booking<?= count($due) === 1 ? '' : 's' ?></span></section>
  <section class="card"><div class="m">Paid so far</div><div class="big"><?= e(money($paidTotal)) ?></div><span class="m"><?= count($history) ?> payment<?= count($history) === 1 ? '' : 's' ?></span></section>
</div>

<section class="card">
  <h2>To pay</h2>
  <?php if ($due): ?>
    <div class="lst">
      <?php foreach ($due as $d): ?>
        <div class="row">
          <div class="g1">
            <b>#<?= (int)$d['booking_id'] ?> · <?= e($d['make_model']) ?></b>
            <span class="m"><?= e(date('D j M Y', strtotime($d['appointment_date']))) ?> · <?= e($d['package_name']) ?> · <?= e(status_label($d['status'])) ?></span>
          </div>
          <b class="price"><?= e(money($d['quoted_price'])) ?></b>
          <a class="btn" href="<?= e($base) ?>pay&amp;id=<?= (int)$d['booking_id'] ?>">Pay online</a>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="m" style="margin:14px 0 0">Prefer cash? You can pay our cleaner or the manager after the service, and they will mark it as paid.</p>
  <?php else: ?>
    <p class="empty">Nothing to pay right now.</p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Payment history</h2>
  <?php if ($history): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Booking</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
          <tr>
            <td><b>#<?= (int)$h['booking_id'] ?></b><span class="m"><?= e($h['make_model']) ?></span></td>
            <td><?= e(date('j M Y, H:i', strtotime($h['payment_date']))) ?></td>
            <td><?= e(money($h['amount'])) ?></td>
            <td><span class="pill"><?= e(method_label($h['method'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty">No payments yet.</p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
