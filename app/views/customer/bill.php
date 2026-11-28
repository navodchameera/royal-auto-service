<?php
defined('APP_PATH') or exit;
// A standalone, print-friendly bill page. Expects: $b (bill row), $number, $backUrl
$base = BASE_URL . '/index.php?url=';
$id   = (int)$b['booking_id'];
$line = function (string $k, string $v): string {
    return '<div class="ln"><span>' . e($k) . '</span><b>' . e($v) . '</b></div>';
};
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bill <?= e($number) ?> · Royal Auto Service</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bill.css">
</head>
<body>
<div class="bar noprint">
  <a class="btn g" href="<?= e($base . $backUrl) ?>">← Back</a>
  <div class="acts">
    <button class="btn g" type="button" onclick="window.print()">Print</button>
    <a class="btn" href="<?= e($base) ?>bill&amp;id=<?= $id ?>&amp;download=1">Download PDF</a>
  </div>
</div>

<main class="sheet">
  <header class="hd">
    <div><div class="brand">Royal Auto Service</div><div class="tag">Car wash and detailing</div></div>
    <div class="r"><div class="doc">BILL</div><div class="tag"><?= e($number) ?></div></div>
  </header>

  <section class="two">
    <div>
      <div class="k">Billed to</div>
      <b><?= e($b['full_name']) ?></b>
      <div><?= e($b['phone']) ?> · <?= e($b['email']) ?></div>
      <div><?= e($b['cust_address']) ?></div>
    </div>
    <div class="r">
      <div class="k">Booking</div>
      <b>#<?= $id ?></b>
      <div>Created <?= e(date('j M Y', strtotime($b['created_at']))) ?></div>
    </div>
  </section>

  <h2>Service</h2>
  <?= $line('Package', $b['package_name'] . ($b['package_desc'] ? ' · ' . $b['package_desc'] : '')) ?>
  <?= $line('Vehicle', $b['make_model'] . ' (' . $b['plate_no'] . ')') ?>
  <?= $line('Appointment', date('D j M Y', strtotime($b['appointment_date'])) . ', ' . substr($b['appointment_time'], 0, 5)) ?>
  <?= $line('Place', location_label($b['location_type']) . ($b['location_type'] === 'customer_site' && $b['job_address'] ? ' · ' . $b['job_address'] : '')) ?>
  <?= $line('Cleaner', $b['cleaner_name'] ?: '–') ?>
  <?= $line('Vehicle condition', $b['condition_level'] ? ucfirst($b['condition_level']) : '–') ?>

  <h2>Timeline</h2>
  <?= $line('Booking created', when_label($b['created_at'])) ?>
  <?= $line('Quotation sent', when_label($b['quotation_date'])) ?>
  <?= $line('Job started (cleaner arrived)', when_label($b['arrived_at'])) ?>
  <?= $line('Job completed', when_label($b['completed_at'])) ?>
  <?= $line('Payment received', when_label($b['payment_date'])) ?>

  <h2>Charges</h2>
  <?= $line($b['package_name'] . ' package (quoted price)', money($b['quoted_price'])) ?>
  <?php if ($b['remarks']): ?><p class="note">Note from the manager: <?= e($b['remarks']) ?></p><?php endif; ?>
  <div class="total"><span>Total</span><b><?= e(money($b['quoted_price'])) ?></b></div>
  <div class="ln"><span>Paid by <?= e(strtolower(method_label((string)$b['pay_method']))) ?> on <?= e(when_label($b['payment_date'])) ?></span><b>Balance due: <?= e(money(0)) ?></b></div>

  <?php if ($b['rating'] !== null): ?>
    <p class="note">Your rating: <?= str_repeat('★', (int)$b['rating']) . str_repeat('☆', 5 - (int)$b['rating']) ?></p>
  <?php endif; ?>

  <footer class="ft">Thank you for choosing Royal Auto Service.</footer>
</main>
</body>
</html>
