<?php
defined('APP_PATH') or exit;
$title     = 'Packages';
$navActive = 'packages';
$base      = BASE_URL . '/index.php?url=';
$post      = $base . 'manage-packages';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Packages</h1>
    <div class="sub">The services customers can book and the price range shown to them. The final price is set in each quotation.</div>
  </div>
  <?php if ($editId === 0): ?><a class="btn" href="<?= e($base) ?>manage-packages&amp;edit=-1">Add a package</a><?php endif; ?>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<?php if ($editId !== 0): ?>
<section class="card">
  <h2><?= $editId > 0 ? 'Edit package' : 'New package' ?></h2>
  <form method="post" action="<?= e($post) ?>" novalidate>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="package_id" value="<?= (int)$editId ?>">

    <label for="package_name">Package name</label>
    <input class="in" id="package_name" name="package_name" maxlength="50" required value="<?= e($old['name']) ?>"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>>
    <p class="err"><?= e($errors['name'] ?? '') ?></p>

    <label for="description">What is included</label>
    <input class="in" id="description" name="description" maxlength="255" value="<?= e($old['description']) ?>"<?= isset($errors['description']) ? ' aria-invalid="true"' : '' ?>>
    <p class="err"><?= e($errors['description'] ?? '') ?></p>

    <div class="two">
      <div>
        <label for="min_price">Lowest price (LKR)</label>
        <input class="in" id="min_price" name="min_price" inputmode="decimal" required value="<?= e($old['min']) ?>"<?= isset($errors['min']) ? ' aria-invalid="true"' : '' ?>>
        <p class="err"><?= e($errors['min'] ?? '') ?></p>
      </div>
      <div>
        <label for="max_price">Highest price (LKR)</label>
        <input class="in" id="max_price" name="max_price" inputmode="decimal" required value="<?= e($old['max']) ?>"<?= isset($errors['max']) ? ' aria-invalid="true"' : '' ?>>
        <p class="err"><?= e($errors['max'] ?? '') ?></p>
      </div>
    </div>

    <div class="acts">
      <button class="btn" type="submit"><?= $editId > 0 ? 'Save changes' : 'Add package' ?></button>
      <a class="btn g" href="<?= e($post) ?>">Cancel</a>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <?php if ($packages): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Package</th><th>Includes</th><th>Price range</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($packages as $p): $pid = (int)$p['package_id']; $active = (int)$p['is_active'] === 1; $name = $p['package_name']; ?>
          <tr>
            <td><b><?= e($name) ?></b></td>
            <td><?= $p['description'] ? e($p['description']) : '<span class="m">–</span>' ?></td>
            <td><?= e(money($p['min_price'])) ?> – <?= e(number_format((float)$p['max_price'])) ?></td>
            <td><?= (int)$p['bookings'] ?></td>
            <td><span class="pill<?= $active ? '' : ' warn' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
            <td>
              <?= Ui::rowMenu('menu_p' . $pid, 'More actions for ' . $name, [
                  ['label' => 'Edit', 'href' => $post . '&edit=' . $pid],
                  $active
                    ? ['label' => 'Make inactive', 'url' => $post, 'csrf' => $csrf, 'fields' => ['action' => 'deactivate', 'package_id' => $pid],
                       'title' => 'Make ' . $name . ' inactive?', 'text' => 'Customers will no longer see this package when they book. Existing bookings are not changed, and you can make it active again any time.', 'ok' => 'Make inactive']
                    : ['label' => 'Make active', 'url' => $post, 'csrf' => $csrf, 'fields' => ['action' => 'activate', 'package_id' => $pid],
                       'title' => 'Make ' . $name . ' active?', 'text' => 'Customers will be able to book this package again.', 'ok' => 'Make active'],
                  ['label' => 'Delete', 'url' => $post, 'csrf' => $csrf, 'fields' => ['action' => 'delete', 'package_id' => $pid],
                   'title' => 'Delete ' . $name . '?', 'text' => 'This permanently deletes the package. It only works for a package that has no bookings.',
                   'ok' => 'Delete package', 'tone' => 'danger', 'red' => true],
              ]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="m" style="margin:14px 0 0">A package that already has bookings cannot be deleted, because the bookings and payments would be deleted with it. Make it inactive instead: it is hidden from customers but your records stay.</p>
  <?php else: ?>
    <p class="empty">No packages yet.</p>
  <?php endif; ?>
</section>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
