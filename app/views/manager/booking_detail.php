<?php
defined('APP_PATH') or exit;
$title     = 'Booking #' . (int)$b['booking_id'];
$navActive = 'bookings';
$base      = BASE_URL . '/index.php?url=';
$id        = (int)$b['booking_id'];
$post      = $base . 'manage-bookings&id=' . $id;
$canCancel = in_array($b['status'], ['requested', 'quoted', 'accepted', 'assigned'], true);
$rejected  = $b['status'] === 'cancelled' && $b['q_status'] === 'rejected';
$canReject = $b['status'] === 'requested';
$canQuote  = in_array($b['status'], ['requested', 'assigned'], true);
$canPay    = in_array($b['status'], ['assigned', 'in_progress', 'completed'], true) && $b['quoted_price'] !== null && !$b['pay_method'];
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <a class="back" href="<?= e($base) ?>manage-bookings">← All bookings</a>
    <h1>Booking #<?= $id ?></h1>
    <div class="sub"><span class="pill<?= $b['status'] === 'requested' ? ' ink' : '' ?><?= $rejected ? ' warn' : '' ?>"><?= e(booking_state($b['status'], $b['q_status'])) ?></span></div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:1fr 1fr;align-items:start">
  <div class="g2">
    <section class="card">
      <h2>Booking</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">When</span><b><?= e(date('D j M Y', strtotime($b['appointment_date']))) ?>, <?= e(substr($b['appointment_time'], 0, 5)) ?></b></div>
        <div class="row"><span class="m g1">Where</span><b><?= e(location_label($b['location_type'])) ?></b></div>
        <?php if ($b['job_address']): ?><div class="row"><span class="m g1">Address</span><b><?= e($b['job_address']) ?></b></div><?php endif; ?>
        <div class="row"><span class="m g1">Package</span><b><?= e($b['package_name']) ?></b></div>
        <div class="row"><span class="m g1">Package range</span><b><?= e(money($b['min_price']) . ' – ' . number_format((float)$b['max_price'])) ?></b></div>
        <div class="row"><span class="m g1">Cleaner</span><b><?= $b['cleaner_name'] ? e($b['cleaner_name']) : '–' ?></b></div>
        <div class="row"><span class="m g1">Requested</span><b><?= e(date('j M Y, H:i', strtotime($b['created_at']))) ?></b></div>
      </div>
    </section>

    <section class="card">
      <h2>Vehicle</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Vehicle</span><b><?= e($b['make_model']) ?></b></div>
        <div class="row"><span class="m g1">Plate</span><b><?= e($b['plate_no']) ?></b></div>
        <div class="row"><span class="m g1">Type</span><b><?= e(ucfirst($b['vehicle_type'])) ?></b></div>
        <?php if ($b['colour']): ?><div class="row"><span class="m g1">Colour</span><b><?= e($b['colour']) ?></b></div><?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2>Customer</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Name</span><b><a href="<?= e($base) ?>manage-customers&amp;id=<?= (int)$b['cust_id'] ?>"><?= e($b['full_name']) ?></a></b></div>
        <div class="row"><span class="m g1">Phone</span><b><a href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a></b></div>
        <div class="row"><span class="m g1">Email</span><b><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></b></div>
        <div class="row"><span class="m g1">Address</span><b><?= e($b['cust_address']) ?></b></div>
      </div>
    </section>
  </div>

  <div class="g2">
    <section class="card">
      <h2>Progress</h2>
      <div class="lst kv">
        <div class="row"><span class="m g1">Booked</span><b><?= e(when_label($b['created_at'])) ?></b></div>
        <div class="row"><span class="m g1"><?= $rejected ? 'Rejected' : 'Quotation sent' ?></span><b><?= e(when_label($b['quotation_date'])) ?></b></div>
        <div class="row"><span class="m g1">Job started</span><b><?= e(when_label($b['arrived_at'])) ?></b></div>
        <div class="row"><span class="m g1">Paid</span><b><?= e(when_label($b['pay_date'])) ?></b></div>
        <div class="row"><span class="m g1">Job completed</span><b><?= e(when_label($b['completed_at'])) ?></b></div>
      </div>
      <?php if ($b['status'] === 'finished'): ?>
        <div class="acts" style="margin-top:12px"><a class="btn g" href="<?= e($base) ?>bill&amp;id=<?= $id ?>">View bill</a></div>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Photos</h2>
      <?php if ($photos): ?>
        <div class="photos">
          <?php foreach ($photos as $p): $label = $slots[$p['photo_area']] ?? 'Photo'; ?>
            <a href="<?= BASE_URL ?>/uploads/booking_photos/<?= e($p['file_path']) ?>" target="_blank" rel="noopener">
              <img src="<?= BASE_URL ?>/uploads/booking_photos/<?= e($p['file_path']) ?>" alt="<?= e($label) ?> photo" loading="lazy">
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty">The customer did not upload any photos.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Payment</h2>
      <?php if ($b['pay_method']): ?>
        <p style="margin:0 0 6px"><span class="pill">Paid · <?= e(method_label($b['pay_method'])) ?></span></p>
        <p class="m" style="margin:0"><?= e(date('j M Y, H:i', strtotime($b['pay_date']))) ?></p>
      <?php elseif ($b['quoted_price'] === null): ?>
        <p class="empty">Nothing to pay until a quotation is sent.</p>
      <?php elseif ($canPay): ?>
        <p class="m" style="margin-top:0">Unpaid. The customer can pay online, or the cleaner can take cash. If the customer paid you in cash, mark it as paid here.</p>
        <?= Ui::postButton($post, $csrf, ['action' => 'pay'], 'Mark as paid (cash)', '',
              ['Mark booking #' . $id . ' as paid?', 'Confirm that the customer paid ' . money($b['quoted_price']) . ' in cash. This is recorded as a cash payment.', 'Mark as paid']) ?>
      <?php else: ?>
        <p class="empty">Unpaid.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Quotation</h2>
      <?php if ($rejected): ?>
        <p style="margin:0 0 8px"><span class="pill warn">Rejected</span></p>
        <p class="m" style="margin:0 0 4px">Reason shown to the customer:</p>
        <p style="margin:0"><?= nl2br(e($b['remarks'])) ?></p>
      <?php elseif ($b['quoted_price'] !== null): ?>
        <div class="qprice"><?= e(money($b['quoted_price'])) ?></div>
        <p style="margin:0 0 8px">
          <?php if ($b['condition_level']): ?><span class="pill">Condition: <?= e(ucfirst($b['condition_level'])) ?></span><?php endif; ?>
          <span class="pill"><?= e(ucfirst((string)$b['q_status'])) ?></span>
        </p>
        <?php if ($b['remarks']): ?><p class="m" style="margin:0 0 12px"><?= nl2br(e($b['remarks'])) ?></p><?php endif; ?>
      <?php else: ?>
        <p class="empty">No quotation has been sent yet.</p>
      <?php endif; ?>
      <div class="acts" style="margin-top:12px">
        <?php if ($canQuote): ?>
          <a class="btn" href="<?= e($base) ?>manage-quotations&amp;id=<?= $id ?>&amp;filter=<?= $b['status'] === 'assigned' ? 'sent' : 'requested' ?>"><?= $b['quoted_price'] !== null ? 'Update quotation' : 'Create quotation' ?></a>
        <?php endif; ?>
        <?php if ($canCancel): ?>
          <?= Ui::postButton($post, $csrf, ['action' => 'cancel'], 'Cancel booking', 'danger',
                ['Cancel booking #' . $id . '?', 'The customer will see this booking as cancelled and the cleaner will be free again for that time.', 'Cancel booking']) ?>
        <?php endif; ?>
      </div>

      <?php if ($canReject): ?>
        <form class="rejbox" method="post" action="<?= e($post) ?>"<?= Ui::confirm('Reject booking #' . $id . '?', 'The booking will be closed and the customer will see your reason. This cannot be undone.', 'Reject booking', 'danger') ?>>
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="reject">
          <label for="reason">Reject this booking</label>
          <textarea class="in" id="reason" name="reason" rows="3" minlength="5" maxlength="500" required placeholder="Reason for rejecting (the customer will see this)"><?= e($rejectOld) ?></textarea>
          <button class="btn danger" type="submit">Reject booking</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
