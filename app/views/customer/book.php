<?php
defined('APP_PATH') or exit;
$title     = 'Book a service';
$navActive = 'book';
require APP_PATH . '/views/layout/app_header.php';

$err = function ($key) use ($errors) { return e($errors[$key] ?? ''); };
?>
<div class="top">
  <div>
    <h1>Book a service</h1>
    <div class="sub">Add photos of your vehicle so we can quote the right price before we arrive.</div>
  </div>
</div>

<?php if (!empty($errors['form'])): ?>
  <div class="alert" role="alert"><?= e($errors['form']) ?></div>
<?php elseif ($errors): ?>
  <div class="alert" role="alert">Please check the highlighted fields below.</div>
<?php endif; ?>
<div class="alert" id="clientErrors" role="alert" hidden></div>

<form id="bookingForm" method="post" action="<?= BASE_URL ?>/index.php?url=book" enctype="multipart/form-data" novalidate>
<input type="hidden" name="csrf" value="<?= e($csrf) ?>">
<div class="g2" style="grid-template-columns:1fr 340px;align-items:start">

<div class="g2">
  <!-- 1. Vehicle and time -->
  <section class="card">
    <h2>1 · Vehicle and time</h2>
    <label for="vehicle_id">Vehicle</label>
    <select class="in" id="vehicle_id" name="vehicle_id">
      <?php foreach ($vehicles as $v): ?>
        <option value="<?= (int)$v['vehicle_id'] ?>"<?= $old['vehicle_id'] === (string)$v['vehicle_id'] ? ' selected' : '' ?>><?= e($v['make_model'] . ' · ' . $v['plate_no']) ?></option>
      <?php endforeach; ?>
      <option value="new"<?= $old['vehicle_id'] === 'new' ? ' selected' : '' ?>>+ Add a new vehicle</option>
    </select>
    <p class="err"><?= $err('vehicle_id') ?></p>

    <div id="newVehicle"<?= $old['vehicle_id'] === 'new' ? '' : ' hidden' ?>>
      <div class="two">
        <div>
          <label for="nv_type">Type</label>
          <select class="in" id="nv_type" name="nv_type">
            <?php foreach ($types as $k => $label): ?>
              <option value="<?= e($k) ?>"<?= $old['nv_type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="err"><?= $err('nv_type') ?></p>
        </div>
        <div>
          <label for="nv_make_model">Make and model</label>
          <input class="in" id="nv_make_model" name="nv_make_model" value="<?= e($old['nv_make_model']) ?>">
          <p class="err"><?= $err('nv_make_model') ?></p>
        </div>
        <div>
          <label for="nv_plate">Plate number</label>
          <input class="in" id="nv_plate" name="nv_plate" value="<?= e($old['nv_plate']) ?>">
          <p class="err"><?= $err('nv_plate') ?></p>
        </div>
        <div>
          <label for="nv_colour">Colour (optional)</label>
          <input class="in" id="nv_colour" name="nv_colour" value="<?= e($old['nv_colour']) ?>">
          <p class="err"><?= $err('nv_colour') ?></p>
        </div>
      </div>
    </div>

    <div class="two">
      <div>
        <label for="appointment_date">Date</label>
        <input class="in" id="appointment_date" name="appointment_date" type="date" min="<?= e($today) ?>" value="<?= e($old['appointment_date']) ?>">
        <p class="err"><?= $err('appointment_date') ?></p>
      </div>
      <div>
        <label for="appointment_time">Time</label>
        <input class="in" id="appointment_time" name="appointment_time" type="time" value="<?= e($old['appointment_time']) ?>">
        <p class="err"><?= $err('appointment_time') ?></p>
      </div>
    </div>
  </section>

  <!-- 2. Package and place -->
  <section class="card">
    <h2>2 · Package and place</h2>
    <fieldset class="fs">
      <legend>Package</legend>
      <div class="opts3">
        <?php foreach ($packages as $p): ?>
          <label class="opt">
            <input type="radio" name="package_id" value="<?= (int)$p['package_id'] ?>"
                   data-name="<?= e($p['package_name']) ?>"
                   data-range="<?= e(money($p['min_price']) . ' – ' . number_format((float)$p['max_price'])) ?>"
                   <?= $old['package_id'] === (string)$p['package_id'] ? 'checked' : '' ?>>
            <b><?= e($p['package_name']) ?></b>
            <span class="m"><?= e($p['description']) ?></span>
            <span class="m"><?= e(money($p['min_price']) . ' – ' . number_format((float)$p['max_price'])) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="err"><?= $err('package_id') ?></p>
    </fieldset>

    <fieldset class="fs">
      <legend>Where should we wash it?</legend>
      <div class="opts2">
        <label class="opt">
          <input type="radio" name="location_type" value="garage" data-label="At Royal Auto Service" <?= $old['location_type'] === 'garage' ? 'checked' : '' ?>>
          <b>At Royal Auto Service</b>
        </label>
        <label class="opt">
          <input type="radio" name="location_type" value="customer_site" data-label="At my location" <?= $old['location_type'] === 'customer_site' ? 'checked' : '' ?>>
          <b>At my location</b>
          <span class="m">Water and power needed. Photos are required.</span>
        </label>
      </div>
      <p class="err"><?= $err('location_type') ?></p>
    </fieldset>

    <div id="addressBlock"<?= $old['location_type'] === 'customer_site' ? '' : ' hidden' ?>>
      <label for="job_address">Address for this booking</label>
      <input class="in" id="job_address" name="job_address" autocomplete="street-address" value="<?= e($old['job_address']) ?>">
      <p class="err"><?= $err('job_address') ?></p>
    </div>
  </section>

  <!-- 3. Photos -->
  <section class="card">
    <h2>3 · Vehicle photos <span class="m" style="font-weight:400">· JPG, PNG or WebP, up to 5 MB each</span></h2>
    <div class="slots">
      <?php foreach ($slots as $key => $label): ?>
        <label class="slot" data-label="<?= e($label) ?>">
          <input type="file" name="photo_<?= e($key) ?>" accept="image/jpeg,image/png,image/webp" aria-label="<?= e($label) ?> photo">
          <img alt="" hidden>
          <span class="cap"><?= e($label) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?php foreach ((array)($errors['photos'] ?? []) as $msg): ?>
      <p class="err"><?= e($msg) ?></p>
    <?php endforeach; ?>
  </section>
</div>

<!-- Summary -->
<aside class="card sticky">
  <h2>Your booking</h2>
  <div class="lst">
    <div class="row"><span class="m g1">Vehicle</span><span id="sumVehicle">–</span></div>
    <div class="row"><span class="m g1">Package</span><span id="sumPackage">–</span></div>
    <div class="row"><span class="m g1">When</span><span id="sumWhen">–</span></div>
    <div class="row"><span class="m g1">Where</span><span id="sumWhere">–</span></div>
    <div class="row"><span class="m g1">Price range</span><b id="sumRange">–</b></div>
  </div>
  <p class="m">The final price depends on the condition of your vehicle. We will send a quotation after we review your photos, and you accept it before anything is booked.</p>
  <button class="btn" type="submit" id="submitBtn" style="width:100%">Submit for quotation</button>
</aside>

</div>
</form>
<script src="<?= BASE_URL ?>/assets/js/booking.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
