<?php defined('APP_PATH') or exit; ?>
<?php $title = 'Log in'; require APP_PATH . '/views/partials/header.php'; ?>
<main class="form">
  <div class="in-w">
    <nav class="tabs" data-tabs aria-label="Account">
      <span class="tab-pill" aria-hidden="true"></span>
      <a href="<?= BASE_URL ?>/index.php?url=login" class="tab on" data-tab="login" aria-current="page">Log in</a>
      <a href="<?= BASE_URL ?>/index.php?url=register" class="tab" data-tab="register">Register</a>
    </nav>

    <h1>Welcome back</h1>
    <p class="sub">Manage your bookings, quotes and payments.</p>

    <?php if (!empty($flash)): ?>
      <div class="alert ok" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
      <div class="alert" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/index.php?url=login" data-validate data-mode="login" novalidate>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

      <label for="login">Username or email</label>
      <input class="in" id="login" name="login" autocomplete="username" value="<?= e($old['login']) ?>">
      <p class="err" id="login-err" aria-live="polite"><?= e($errors['login'] ?? '') ?></p>

      <label for="password">Password</label>
      <div class="pw">
        <input class="in" id="password" name="password" type="password" autocomplete="current-password">
        <button type="button" data-toggle-pw aria-controls="password">Show</button>
      </div>
      <p class="err" id="password-err" aria-live="polite"></p>

      <button class="btn" type="submit">Log in</button>
    </form>

    <p class="foot">New here? <a href="<?= BASE_URL ?>/index.php?url=register">Create an account</a></p>
  </div>
</main>
<?php require APP_PATH . '/views/partials/footer.php'; ?>
