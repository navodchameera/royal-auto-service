<?php
// Front controller: every request comes through here.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

define('APP_PATH', __DIR__ . '/app');
require APP_PATH . '/config/config.php';

foreach (['core/Database', 'core/Controller', 'core/PhotoUploader', 'core/Ui', 'core/SimplePdf', 'models/User', 'models/Vehicle', 'models/Booking', 'models/Dashboard', 'models/Manage', 'models/Payment', 'models/Jobs', 'models/MyBookings', 'models/ManageMore', 'models/Bill', 'models/Package', 'models/Photo',
          'controllers/AuthController', 'controllers/HomeController', 'controllers/AccountController', 'controllers/BookingController', 'controllers/VehicleController', 'controllers/ManageBookingsController', 'controllers/ManageQuotationsController', 'controllers/ManageCustomersController', 'controllers/ManageCleanersController', 'controllers/PaymentController', 'controllers/CleanerJobsController', 'controllers/MyBookingsController', 'controllers/ManageJobsController', 'controllers/ManagePaymentsController', 'controllers/ManageReviewsController', 'controllers/ManagePackagesController', 'controllers/BillController'] as $file) {
    require APP_PATH . '/' . $file . '.php';
}

// A banned (or deleted) account is signed out on its next click
if (isset($_SESSION['user'])) {
    $current = (new User())->findById((int)$_SESSION['user']['id']);
    if (!$current || $current['status'] !== 'active') {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your account is blocked or no longer exists. Please contact the manager.';
        header('Location: ' . BASE_URL . '/index.php?url=login');
        exit;
    }
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
    'bookings' => ['MyBookingsController', 'index'],
    'vehicles' => ['VehicleController', 'index'],
    'manage-bookings'   => ['ManageBookingsController', 'index'],
    'manage-quotations' => ['ManageQuotationsController', 'index'],
    'manage-customers'  => ['ManageCustomersController', 'index'],
    'manage-cleaners'   => ['ManageCleanersController', 'index'],
    'manage-jobs'       => ['ManageJobsController', 'index'],
    'manage-payments'   => ['ManagePaymentsController', 'index'],
    'manage-reviews'    => ['ManageReviewsController', 'index'],
    'manage-packages'   => ['ManagePackagesController', 'index'],
    'bill'              => ['BillController', 'show'],
    'my-jobs'           => ['CleanerJobsController', 'index'],
    'payments'          => ['PaymentController', 'index'],
    'pay'               => ['PaymentController', 'pay'],
];

if (!isset($routes[$url])) {
    http_response_code(404);
    exit('Page not found');
}

[$class, $method] = $routes[$url];
(new $class())->$method();
