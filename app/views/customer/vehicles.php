<?php
defined('APP_PATH') or exit;
$title     = 'My vehicles';
$navActive = 'vehicles';
$base      = BASE_URL . '/index.php?url=';
require APP_PATH . '/views/layout/app_header.php';

// The four vehicle fields, used by the Add dialog and by every Edit panel.
// $sfx makes the ids unique on the page, e.g. "_add" or "_5".
$fields = function (string $sfx, array $vals, array $errs) use ($types) {
    ob_start(); ?>
    <div class="two">
      <div>
        <label for="vehicle_type<?= $sfx ?>">Type</label>
        <select class="in" id="vehicle_type<?= $sfx ?>" name="vehicle_type">
          <option value=""<?= $vals['vehicle_type'] === '' ? ' selected' : '' ?>>-- Select type --</option>
          <?php foreach ($types as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= $vals['vehicle_type'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="err" id="vehicle_type<?= $sfx ?>-err"><?= e($errs['vehicle_type'] ?? '') ?></p>
      </div>
      <div>
        <label for="make_model<?= $sfx ?>">Make and model</label>
        <input class="in" id="make_model<?= $sfx ?>" name="make_model" value="<?= e($vals['make_model']) ?>">
        <p class="err" id="make_model<?= $sfx ?>-err" aria-live="polite"><?= e($errs['make_model'] ?? '') ?></p>
      </div>
      <div>
        <label for="plate_no<?= $sfx ?>">Plate number</label>
        <input class="in" id="plate_no<?= $sfx ?>" name="plate_no" value="<?= e($vals['plate_no']) ?>">
        <p class="err" id="plate_no<?= $sfx ?>-err" aria-live="polite"><?= e($errs['plate_no'] ?? '') ?></p>
      </div>
      <div>
        <label for="colour<?= $sfx ?>">Colour (optional)</label>
        <input class="in" id="colour<?= $sfx ?>" name="colour" value="<?= e($vals['colour']) ?>">
        <p class="err" id="colour<?= $sfx ?>-err" aria-live="polite"><?= e($errs['colour'] ?? '') ?></p>
      </div>
    </div>
    <?php return ob_get_clean();
};

$isAdd = $state['mode'] === 'add';
?>
<div class="top">
  <div>
    <h1>My vehicles</h1>
    <div class="sub">Add each vehicle once, then pick it when you book.</div>
  </div>
  <button class="btn" type="button" data-open-add>Add a vehicle</button>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($formError !== ''): ?><div class="alert" role="alert"><?= e($formError) ?></div><?php endif; ?>

<div class="g2">
  <?php if (!$vehicles): ?>
    <section class="card"><p class="empty">No vehicles yet. Use "Add a vehicle" to add your first one.</p></section>
  <?php endif; ?>

  <?php foreach ($vehicles as $v):
      $id      = (int)$v['vehicle_id'];
      $active  = (int)$v['active_bookings'] > 0;
      $count   = (int)$v['bookings'];
      $isEdit  = $state['mode'] === 'update' && $state['id'] === $id;
      $vals    = $isEdit ? $state['old'] : [
          'vehicle_type' => $v['vehicle_type'], 'make_model' => $v['make_model'],
          'plate_no' => $v['plate_no'], 'colour' => (string)$v['colour'],
      ];
      $errs    = $isEdit ? $state['errors'] : [];
  ?>
    <section class="card vcard">
      <div class="vi"><?= $svg('vehicles') ?></div>

      <div class="vinfo">
        <h2><?= e($v['make_model']) ?></h2>
        <div class="vmeta">
          <span class="plate"><?= e($v['plate_no']) ?></span>
          <?php if ($v['colour']): ?><span class="m"><?= e($v['colour']) ?></span><?php endif; ?>
        </div>
        <div class="vtags">
          <span class="pill"><?= e($types[$v['vehicle_type']] ?? ucfirst($v['vehicle_type'])) ?></span>
          <span class="pill"><?= $count ?> booking<?= $count === 1 ? '' : 's' ?></span>
          <?php if ($active): ?><span class="pill ink">In an active booking</span><?php endif; ?>
        </div>
      </div>

      <div class="acts">
        <a class="btn" href="<?= e($base) ?>book&amp;vehicle=<?= $id ?>">Book</a>
        <?php if (!$active): ?>
          <button class="btn g" type="button" data-edit="edit_<?= $id ?>" aria-expanded="<?= $isEdit ? 'true' : 'false' ?>" aria-controls="edit_<?= $id ?>">Edit</button>
        <?php endif; ?>
        <?php if ($count === 0): ?>
          <form method="post" action="<?= e($base) ?>vehicles"<?= Ui::confirm('Delete ' . $v['make_model'] . '?', 'This vehicle will be removed from your account. This cannot be undone.', 'Delete vehicle', 'danger') ?>>
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="vehicle_id" value="<?= $id ?>">
            <button class="btn danger" type="submit">Delete</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!$active): ?>
        <form class="vedit" id="edit_<?= $id ?>" method="post" action="<?= e($base) ?>vehicles" data-validate data-mode="vehicle" novalidate<?= $isEdit ? '' : ' hidden' ?>>
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="vehicle_id" value="<?= $id ?>">
          <?= $fields('_' . $id, $vals, $errs) ?>
          <div class="acts">
            <button class="btn" type="submit">Save changes</button>
            <button class="btn g" type="button" data-cancel-edit>Cancel</button>
          </div>
        </form>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<!-- Add a vehicle (pop-up) -->
<dialog class="dlg" id="addVehicleDialog" aria-labelledby="addTitle"<?= ($isAdd || $openAdd) ? ' data-open' : '' ?>>
  <div class="dh">
    <h2 id="addTitle">Add a vehicle</h2>
    <button class="x" type="button" data-close-dialog aria-label="Close">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B1B2B" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>Close
    </button>
  </div>
  <form method="post" action="<?= e($base) ?>vehicles" data-validate data-mode="vehicle" novalidate>
    <div class="db">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="add">
      <?= $fields('_add', $isAdd ? $state['old'] : ['vehicle_type' => '', 'make_model' => '', 'plate_no' => '', 'colour' => ''], $isAdd ? $state['errors'] : []) ?>
    </div>
    <div class="df"><button class="btn" type="submit" style="width:100%">Save vehicle</button></div>
  </form>
</dialog>

<script src="<?= BASE_URL ?>/assets/js/vehicles.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
