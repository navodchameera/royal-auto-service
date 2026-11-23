<?php defined('APP_PATH') or exit; ?>
<?php $title = 'Register'; require APP_PATH . '/views/partials/header.php'; ?>
<main class="form">
  <div class="in-w">
    <nav class="tabs" data-tabs aria-label="Account">
      <span class="tab-pill" aria-hidden="true"></span>
      <a href="<?= BASE_URL ?>/index.php?url=login" class="tab" data-tab="login">Log in</a>
      <a href="<?= BASE_URL ?>/index.php?url=register" class="tab on" data-tab="register" aria-current="page">Register</a>
    </nav>

    <h1>Create your account</h1>
    <p class="sub">You can add your vehicle after you sign up.</p>

    <?php if (!empty($errors['form'])): ?>
      <div class="alert" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/index.php?url=register" data-validate data-mode="register" novalidate>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

      <div class="two">
        <div>
          <label for="full_name">Full name</label>
          <input class="in" id="full_name" name="full_name" autocomplete="name" value="<?= e($old['full_name']) ?>">
          <p class="err" id="full_name-err" aria-live="polite"><?= e($errors['full_name'] ?? '') ?></p>
        </div>
        <div>
          <label for="username">Username</label>
          <input class="in" id="username" name="username" autocomplete="username" value="<?= e($old['username']) ?>">
          <p class="err" id="username-err" aria-live="polite"><?= e($errors['username'] ?? '') ?></p>
        </div>
      </div>

      <div class="two">
        <div>
          <label for="email">Email</label>
          <input class="in" id="email" name="email" type="email" autocomplete="email" value="<?= e($old['email']) ?>">
          <p class="err" id="email-err" aria-live="polite"><?= e($errors['email'] ?? '') ?></p>
        </div>
        <div>
          <label for="phone">Phone</label>
          <input class="in" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e($old['phone']) ?>">
          <p class="err" id="phone-err" aria-live="polite"><?= e($errors['phone'] ?? '') ?></p>
        </div>
      </div>

      <div class="two">
        <div>
          <label for="password">Password</label>
          <div class="pw">
            <input class="in" id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-hint">
            <button type="button" data-toggle-pw aria-controls="password">Show</button>
          </div>
          <p class="err" id="password-err" aria-live="polite"><?= e($errors['password'] ?? '') ?></p>
        </div>
        <div>
          <label for="confirm_password">Confirm password</label>
          <div class="pw">
            <input class="in" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password">
            <button type="button" data-toggle-pw aria-controls="confirm_password">Show</button>
          </div>
          <p class="err" id="confirm_password-err" aria-live="polite"><?= e($errors['confirm_password'] ?? '') ?></p>
        </div>
      </div>
      <p class="hint" id="password-hint">Password: at least 8 characters, with a letter and a number.</p>

      <label for="address">Address</label>
      <input class="in" id="address" name="address" autocomplete="street-address" value="<?= e($old['address']) ?>">
      <p class="err" id="address-err" aria-live="polite"><?= e($errors['address'] ?? '') ?></p>

      <div class="ck">
        <input id="terms" name="terms" type="checkbox">
        <label for="terms">I agree to the</label>
        <button class="lk" type="button" data-open-terms>Terms and Conditions</button>
      </div>
      <p class="err" id="terms-err" aria-live="polite"><?= e($errors['terms'] ?? '') ?></p>

      <button class="btn" type="submit" id="registerBtn" disabled>Create account</button>
    </form>

    <p class="foot">Already have an account? <a href="<?= BASE_URL ?>/index.php?url=login">Log in</a></p>
  </div>
</main>
<?php require APP_PATH . '/views/partials/terms.php'; ?>
<?php require APP_PATH . '/views/partials/footer.php'; ?>
