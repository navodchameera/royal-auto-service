<?php
defined('APP_PATH') or exit;
// Expects: $user (session user), $title, $navActive
$settings = ['Settings', BASE_URL . '/index.php?url=settings'];
$navByRole = [
    'customer' => [
        'home'     => ['Home',           BASE_URL . '/index.php?url=home'],
        'book'     => ['Book a service', BASE_URL . '/index.php?url=book'],
        'bookings' => ['My bookings',    '#'],
        'vehicles' => ['My vehicles',    '#'],
        'payments' => ['Payments',       '#'],
        'reviews'  => ['Reviews',        '#'],
        'settings' => $settings,
    ],
    'manager' => ['home' => ['Home', BASE_URL . '/index.php?url=home'], 'settings' => $settings],
    'cleaner' => ['home' => ['Home', BASE_URL . '/index.php?url=home'], 'settings' => $settings],
];
$navItems = $navByRole[$user['role']] ?? $navByRole['customer'];
$parts    = preg_split('/\s+/', trim($user['name']));
$initials = strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Royal Auto Service') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="logo">Royal Auto <span>Service</span></div>
    <nav aria-label="Main">
      <?php foreach ($navItems as $key => [$label, $href]): ?>
        <a class="nav<?= $key === ($navActive ?? '') ? ' on' : '' ?>" href="<?= e($href) ?>"<?= $key === ($navActive ?? '') ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <div class="av"><?= e($initials) ?></div>
      <div>
        <?= e($user['name']) ?>
        <small><?= e(ucfirst($user['role'])) ?> · <a class="out" href="<?= BASE_URL ?>/index.php?url=logout">Log out</a></small>
      </div>
    </div>
  </aside>
  <main class="main">
