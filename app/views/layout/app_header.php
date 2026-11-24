<?php
defined('APP_PATH') or exit;
// Expects: $user (session user), $title, $navActive

$settings = ['Settings', BASE_URL . '/index.php?url=settings'];
$navByRole = [
    'customer' => [
        'home'     => ['Home',           BASE_URL . '/index.php?url=home'],
        'book'     => ['Book a service', BASE_URL . '/index.php?url=book'],
        'bookings' => ['My bookings',    BASE_URL . '/index.php?url=bookings'],
        'vehicles' => ['My vehicles',    BASE_URL . '/index.php?url=vehicles'],
        'payments' => ['Payments',       '#'],
        'reviews'  => ['Reviews',        '#'],
        'settings' => $settings,
    ],
    'manager' => [
        'home'       => ['Dashboard',  BASE_URL . '/index.php?url=home'],
        'bookings'   => ['Bookings',   BASE_URL . '/index.php?url=manage-bookings'],
        'quotations' => ['Quotations', BASE_URL . '/index.php?url=manage-quotations'],
        'jobs'       => ['Jobs',       '#'],
        'cleaners'   => ['Cleaners',   '#'],
        'customers'  => ['Customers',  BASE_URL . '/index.php?url=manage-customers'],
        'packages'   => ['Packages',   '#'],
        'payments'   => ['Payments',   '#'],
        'reviews'    => ['Reviews',    '#'],
        'settings'   => $settings,
    ],
    'cleaner' => ['home' => ['Home', BASE_URL . '/index.php?url=home'], 'settings' => $settings],
];
$navItems = $navByRole[$user['role']] ?? $navByRole['customer'];

// Simple line icons (24x24). Shown alone when the menu is collapsed.
$icons = [
    'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>',
    'book'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/>',
    'bookings' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
    'vehicles' => '<path d="M4 17H3v-5l2-5h14l2 5v5h-1"/><path d="M5 12h14"/><circle cx="7.5" cy="17" r="1.8"/><circle cx="16.5" cy="17" r="1.8"/>',
    'payments' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'reviews'  => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
    'settings' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
    'logout'   => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
    'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
    'quotations' => '<path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4M10 12h6M10 16h6"/>',
    'jobs'     => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9zM9 14l2 2 4-4"/>',
    'cleaners' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 5.5a3 3 0 0 1 0 5.5M18 14.5c1.7.8 3 2.5 3 5.5"/>',
    'customers' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/>',
    'packages' => '<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
];
$svg = function (string $key, string $class = '') use ($icons) {
    return '<svg class="ic ' . $class . '" viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$key] ?? '') . '</svg>';
};

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
<div class="app" id="app">
  <div class="rail">
    <aside class="side" id="side">
      <div class="side-top">
        <div class="logo">Royal Auto <span>Service</span></div>
        <button class="menu" id="menuBtn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="side">
          <?= $svg('menu', 'ic-menu') ?><?= $svg('close', 'ic-close') ?>
        </button>
      </div>
      <nav aria-label="Main">
        <?php foreach ($navItems as $key => [$label, $href]): $on = $key === ($navActive ?? ''); ?>
          <a class="nav<?= $on ? ' on' : '' ?>" href="<?= e($href) ?>" title="<?= e($label) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
            <?= $svg($key) ?><span class="lbl"><?= e($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="who">
        <div class="av"><?= e($initials) ?></div>
        <div class="who-text"><?= e($user['name']) ?><small><?= e(ucfirst($user['role'])) ?></small></div>
      </div>
      <a class="nav" href="<?= BASE_URL ?>/index.php?url=logout" title="Log out"><?= $svg('logout') ?><span class="lbl">Log out</span></a>
    </aside>
  </div>
  <div class="scrim" id="scrim" hidden></div>
  <main class="main">
