<?php
// Front controller: every request comes through here.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

define('APP_PATH', __DIR__ . '/app');
require APP_PATH . '/config/config.php';

foreach (['core/Database', 'core/Controller', 'core/PhotoUploader', 'models/User', 'models/Vehicle', 'models/Booking', 'models/Dashboard', 'models/Package', 'models/Photo',
          'controllers/AuthController', 'controllers/HomeController', 'controllers/AccountController', 'controllers/BookingController', 'controllers/VehicleController'] as $file) {
    require APP_PATH . '/' . $file . '.php';
}

$url = trim($_GET['url'] ?? 'login', '/');
if ($url === '') { $url = 'login'; }

$routes = [
    'login'    => ['AuthController', 'login'],
    'register' => ['AuthController', 'register'],
    'logout'   => ['AuthController', 'logout'],
    'home'     => ['HomeController', 'index'],
    'settings' => ['AccountController', 'settings'],
    'book'     => ['BookingController', 'create'],
    'bookings' => ['BookingController', 'index'],
    'vehicles' => ['VehicleController', 'index'],
];

if (!isset($routes[$url])) {
    http_response_code(404);
    exit('Page not found');
}

[$class, $method] = $routes[$url];
(new $class())->$method();
