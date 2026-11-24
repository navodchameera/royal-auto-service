<?php
defined('APP_PATH') or exit;
$title     = 'Booking #' . (int)$b['booking_id'];
$navActive = 'bookings';
$base      = BASE_URL . '/index.php?url=';
$id        = (int)$b['booking_id'];
$canCancel = in_array($b['status'], ['requested', 'quoted', 'accepted', 'assigned'], true);
$canQuote  = in_array($b['status'], ['requested', 'quoted'], true);
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <a class="back" href="<?= e($base) ?>manage-bookings">← All bookings</a>
    <h1>Booking #<?= $id ?></h1>
    <div class="sub"><span class="pill<?= $b['status'] === 'requested' ? ' ink' : '' ?>"><?= e(status_label($b['status'])) ?></span></div>
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
      <h2>Quotation</h2>
      <?php if ($b['quoted_price'] !== null): ?>
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
          <a class="btn" href="<?= e($base) ?>manage-quotations&amp;id=<?= $id ?>&amp;filter=<?= $b['status'] === 'quoted' ? 'quoted' : 'requested' ?>"><?= $b['quoted_price'] !== null ? 'Update quotation' : 'Create quotation' ?></a>
        <?php endif; ?>
        <?php if ($canCancel): ?>
          <form method="post" action="<?= e($base) ?>manage-bookings&amp;id=<?= $id ?>" data-confirm="Cancel booking #<?= $id ?>? The customer will see it as cancelled.">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="cancel">
            <button class="btn danger" type="submit">Cancel booking</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/confirm.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
