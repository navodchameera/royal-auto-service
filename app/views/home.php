<?php
defined('APP_PATH') or exit;
$title     = 'Home';
$navActive = 'home';
require APP_PATH . '/views/layout/app_header.php';
?>
<div class="top">
  <div>
    <h1>Welcome, <?= e(explode(' ', trim($user['name']))[0]) ?></h1>
    <div class="sub">You are logged in as a <?= e($user['role']) ?>. Your home page will go here.</div>
  </div>
</div>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
