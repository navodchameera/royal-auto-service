<?php
defined('APP_PATH') or exit;
$title     = 'Settings';
$navActive = 'settings';
$action    = BASE_URL . '/index.php?url=settings';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Settings</h1>
    <div class="sub">Manage how you sign in<?= $user['role'] === 'customer' ? ' and where we find you' : '' ?>.</div>
  </div>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:1fr 1fr;align-items:start">
  <div class="g2">
    <section class="card">
      <h2>Username</h2>
      <form method="post" action="<?= e($action) ?>" data-validate data-mode="settings" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="username">
        <label for="username">Username</label>
        <input class="in" id="username" name="username" autocomplete="username" value="<?= e($old['username']) ?>">
        <p class="err" id="username-err" aria-live="polite"><?= e($errors['username'] ?? '') ?></p>
        <button class="btn" type="submit">Save username</button>
      </form>
    </section>

    <section class="card">
      <h2>Password</h2>
      <form method="post" action="<?= e($action) ?>" data-validate data-mode="settings" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="password">

        <label for="current_password">Current password</label>
        <div class="pw">
          <input class="in" id="current_password" name="current_password" type="password" autocomplete="current-password">
          <button type="button" data-toggle-pw aria-controls="current_password">Show</button>
        </div>
        <p class="err" id="current_password-err" aria-live="polite"><?= e($errors['current_password'] ?? '') ?></p>

        <label for="new_password">New password</label>
        <div class="pw">
          <input class="in" id="new_password" name="new_password" type="password" autocomplete="new-password" aria-describedby="np-hint">
          <button type="button" data-toggle-pw aria-controls="new_password">Show</button>
        </div>
        <p class="hint" id="np-hint">At least 8 characters, with a letter and a number.</p>
        <p class="err" id="new_password-err" aria-live="polite"><?= e($errors['new_password'] ?? '') ?></p>

        <label for="confirm_password">Confirm new password</label>
        <div class="pw">
          <input class="in" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password">
          <button type="button" data-toggle-pw aria-controls="confirm_password">Show</button>
        </div>
        <p class="err" id="confirm_password-err" aria-live="polite"><?= e($errors['confirm_password'] ?? '') ?></p>

        <button class="btn" type="submit">Change password</button>
      </form>
    </section>
  </div>

  <div class="g2">
    <section class="card">
      <h2>Your details</h2>
      <div class="lst">
        <div class="row"><div class="g1"><span class="m">Name</span><b><?= e($account['full_name']) ?></b></div></div>
        <div class="row"><div class="g1"><span class="m">Email</span><b><?= e($account['email']) ?></b></div></div>
        <div class="row"><div class="g1"><span class="m">Phone</span><b><?= e($account['phone']) ?></b></div></div>
        <div class="row"><div class="g1"><span class="m">Role</span><b><?= e(ucfirst($account['role'])) ?></b></div></div>
      </div>
      <p class="m" style="margin:14px 0 0">To change your name, email or phone, please contact the manager.</p>
    </section>

    <?php if ($user['role'] === 'customer'): ?>
    <section class="card">
      <h2>Address</h2>
      <form method="post" action="<?= e($action) ?>" data-validate data-mode="settings" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="address">
        <label for="address">Address</label>
        <input class="in" id="address" name="address" autocomplete="street-address" value="<?= e($old['address']) ?>">
        <p class="err" id="address-err" aria-live="polite"><?= e($errors['address'] ?? '') ?></p>
        <button class="btn" type="submit">Save address</button>
      </form>
    </section>
    <?php endif; ?>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
