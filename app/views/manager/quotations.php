<?php
defined('APP_PATH') or exit;
$title     = 'Quotations';
$navActive = 'quotations';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
$err = function ($key) use ($errors) { return e($errors[$key] ?? ''); };
?>
<div class="top">
  <div>
    <h1>Quotations</h1>
    <div class="sub">Review the photos, set the condition, send the price.</div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>manage-quotations&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<div class="g2" style="grid-template-columns:300px 1fr;align-items:start">
  <div class="qlist">
    <?php foreach ($queue as $item): $on = $b && (int)$b['booking_id'] === (int)$item['booking_id']; ?>
      <a class="qitem<?= $on ? ' on' : '' ?>" href="<?= e($base) ?>manage-quotations&amp;filter=<?= e($filter) ?>&amp;id=<?= (int)$item['booking_id'] ?>"<?= $on ? ' aria-current="true"' : '' ?>>
        <b>#<?= (int)$item['booking_id'] ?> · <?= e($item['make_model']) ?></b>
        <span class="m"><?= e($item['full_name']) ?> · <?= e(date('j M', strtotime($item['appointment_date']))) ?>, <?= e(substr($item['appointment_time'], 0, 5)) ?></span><br>
        <span class="pill"><?= e($item['package_name']) ?></span>
        <span class="pill"><?= (int)$item['photo_count'] ?> photo<?= (int)$item['photo_count'] === 1 ? '' : 's' ?></span>
      </a>
    <?php endforeach; ?>
    <?php if (!$queue): ?>
      <section class="card"><p class="empty"><?= $filter === 'requested' ? 'No requests are waiting for a quotation.' : 'No quotations are waiting for a customer reply.' ?></p></section>
    <?php endif; ?>
  </div>

  <div class="g2">
    <?php if (!$b): ?>
      <section class="card"><p class="empty">Choose a booking on the left.</p></section>
    <?php else: $id = (int)$b['booking_id']; ?>

      <section class="card">
        <h2>Booking #<?= $id ?> · <?= e($b['make_model']) ?> <span class="m" style="font-weight:400"><?= e($b['plate_no']) ?></span></h2>
        <div class="lst kv">
          <div class="row"><span class="m g1">Customer</span><b><a href="<?= e($base) ?>manage-customers&amp;id=<?= (int)$b['cust_id'] ?>"><?= e($b['full_name']) ?></a> · <?= e($b['phone']) ?></b></div>
          <div class="row"><span class="m g1">Package</span><b><?= e($b['package_name']) ?> (<?= e(money($b['min_price']) . ' – ' . number_format((float)$b['max_price'])) ?>)</b></div>
          <div class="row"><span class="m g1">When</span><b><?= e(date('D j M Y', strtotime($b['appointment_date']))) ?>, <?= e(substr($b['appointment_time'], 0, 5)) ?></b></div>
          <div class="row"><span class="m g1">Where</span><b><?= e(location_label($b['location_type'])) ?><?= $b['job_address'] ? ' · ' . e($b['job_address']) : '' ?></b></div>
        </div>
      </section>

      <section class="card">
        <h2>Customer photos</h2>
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
          <p class="empty">No photos were uploaded for this booking.</p>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2>Your quotation</h2>
        <?php if ($canQuote): ?>
          <form method="post" action="<?= e($base) ?>manage-quotations&amp;filter=<?= e($filter) ?>&amp;id=<?= $id ?>" data-validate data-mode="quotation" novalidate>
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="booking_id" value="<?= $id ?>">

            <fieldset class="fs">
              <legend>Vehicle condition</legend>
              <div class="radios">
                <?php foreach ($levels as $k => $label): ?>
                  <label><input type="radio" name="condition_level" value="<?= e($k) ?>"<?= $old['condition_level'] === $k ? ' checked' : '' ?>><?= e($label) ?></label>
                <?php endforeach; ?>
              </div>
              <p class="err" id="condition_level-err"><?= $err('condition_level') ?></p>
            </fieldset>

            <label for="quoted_price">Price (LKR)</label>
            <div class="money"><span>LKR</span><input class="in" id="quoted_price" name="quoted_price" inputmode="decimal" value="<?= e($old['quoted_price']) ?>"></div>
            <p class="hint">Package range: <?= e(money($b['min_price']) . ' – ' . number_format((float)$b['max_price'])) ?></p>
            <p class="err" id="quoted_price-err" aria-live="polite"><?= $err('quoted_price') ?></p>

            <label for="remarks">Remarks for the customer (optional)</label>
            <textarea class="in" id="remarks" name="remarks" rows="4"><?= e($old['remarks']) ?></textarea>
            <p class="err" id="remarks-err" aria-live="polite"><?= $err('remarks') ?></p>

            <fieldset class="fs">
              <legend>Assign a cleaner</legend>
              <p class="hint" style="margin-top:0">Cleaners with no other job within <?= (int)JOB_HOURS ?> hours of <?= e(substr($b['appointment_time'], 0, 5)) ?> on <?= e(date('D j M', strtotime($b['appointment_date']))) ?>.</p>
              <?php if ($free): ?>
                <select class="in" id="cleaner_id" name="cleaner_id" aria-label="Cleaner">
                  <option value=""<?= $old['cleaner_id'] === '' ? ' selected' : '' ?>>-- Select cleaner --</option>
                  <?php foreach ($free as $c): ?>
                    <option value="<?= (int)$c['user_id'] ?>"<?= $old['cleaner_id'] === (string)$c['user_id'] ? ' selected' : '' ?>><?= e($c['full_name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="err" id="cleaner_id-err" aria-live="polite"><?= $err('cleaner_id') ?></p>
              <?php else: ?>
                <div class="alert" role="alert">No cleaner is free at this date and time, so this quotation cannot be sent. You can cancel the booking from its page and ask the customer to choose another time.</div>
              <?php endif; ?>
              <?php if ($busy): ?>
                <p class="hint">Busy then: <?= e(implode(', ', array_column($busy, 'full_name'))) ?></p>
              <?php endif; ?>
            </fieldset>

            <button class="btn" type="submit"<?= $free ? '' : ' disabled' ?>><?= $b['status'] === 'quoted' ? 'Update quotation' : 'Send quotation' ?></button>
          </form>
        <?php else: ?>
          <p class="empty">This booking is <?= e(strtolower(status_label($b['status']))) ?>, so its quotation can no longer be changed.</p>
        <?php endif; ?>
      </section>

    <?php endif; ?>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
