<?php
defined('APP_PATH') or exit;
$title     = 'My vehicles';
$navActive = 'vehicles';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>My vehicles</h1>
    <div class="sub">Add each vehicle once, then pick it when you book.</div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:1.3fr 1fr;align-items:start">

  <div class="g2">
    <?php if ($vehicles): ?>
      <?php foreach ($vehicles as $v): ?>
        <section class="card">
          <div class="vc">
            <div class="g1">
              <h2 style="margin:0 0 2px"><?= e($v['make_model']) ?></h2>
              <div class="m"><?= e($v['plate_no']) ?><?= $v['colour'] ? ' · ' . e($v['colour']) : '' ?></div>
              <p style="margin:10px 0 0">
                <span class="pill"><?= e($types[$v['vehicle_type']] ?? ucfirst($v['vehicle_type'])) ?></span>
                <span class="pill"><?= (int)$v['bookings'] ?> booking<?= (int)$v['bookings'] === 1 ? '' : 's' ?></span>
              </p>
            </div>
            <div class="acts">
              <a class="btn" href="<?= e($base) ?>book&amp;vehicle=<?= (int)$v['vehicle_id'] ?>">Book</a>
              <a class="btn g" href="<?= e($base) ?>vehicles&amp;edit=<?= (int)$v['vehicle_id'] ?>">Edit</a>
              <?php if ((int)$v['bookings'] === 0): ?>
                <form method="post" action="<?= e($base) ?>vehicles" data-confirm="Delete <?= e($v['make_model']) ?>?">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>">
                  <button class="btn danger" type="submit">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </section>
      <?php endforeach; ?>
    <?php else: ?>
      <section class="card"><p class="empty">No vehicles yet. Add your first one using the form.</p></section>
    <?php endif; ?>
  </div>

  <section class="card sticky">
    <h2><?= $editing ? 'Edit vehicle' : 'Add a vehicle' ?></h2>
    <form method="post" action="<?= e($base) ?>vehicles<?= $editing ? '&amp;edit=' . (int)$editing['vehicle_id'] : '' ?>" data-validate data-mode="vehicle" novalidate>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
      <?php if ($editing): ?><input type="hidden" name="vehicle_id" value="<?= (int)$editing['vehicle_id'] ?>"><?php endif; ?>

      <label for="vehicle_type">Type</label>
      <select class="in" id="vehicle_type" name="vehicle_type">
        <?php foreach ($types as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $old['vehicle_type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="err" id="vehicle_type-err"><?= e($errors['vehicle_type'] ?? '') ?></p>

      <label for="make_model">Make and model</label>
      <input class="in" id="make_model" name="make_model" value="<?= e($old['make_model']) ?>">
      <p class="err" id="make_model-err" aria-live="polite"><?= e($errors['make_model'] ?? '') ?></p>

      <label for="plate_no">Plate number</label>
      <input class="in" id="plate_no" name="plate_no" value="<?= e($old['plate_no']) ?>">
      <p class="err" id="plate_no-err" aria-live="polite"><?= e($errors['plate_no'] ?? '') ?></p>

      <label for="colour">Colour (optional)</label>
      <input class="in" id="colour" name="colour" value="<?= e($old['colour']) ?>">
      <p class="err" id="colour-err" aria-live="polite"><?= e($errors['colour'] ?? '') ?></p>

      <div class="acts">
        <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Save vehicle' ?></button>
        <?php if ($editing): ?><a class="btn g" href="<?= e($base) ?>vehicles">Cancel</a><?php endif; ?>
      </div>
    </form>
  </section>
</div>
<script src="<?= BASE_URL ?>/assets/js/vehicles.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
