<?php
date_default_timezone_set('Asia/Colombo');
defined('APP_PATH') or exit;
define('DB_HOST', 'localhost');
define('DB_NAME', 'royal_auto');
define('DB_USER', 'root');
define('DB_PASS', '');

// Web path of the folder that holds index.php.
// Works in a sub folder (/royal-auto) and at a domain root ('').
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

// Escape output to prevent XSS
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value): string {
    return 'LKR ' . number_format((float)$value);
}

function status_label(string $status): string {
    $labels = [
        'requested'   => 'Waiting for quotation',
        'quoted'      => 'Quotation ready',
        'accepted'    => 'Accepted',
        'assigned'    => 'Cleaner assigned',
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function location_label(string $type): string {
    return $type === 'customer_site' ? 'At my location' : 'At Royal Auto Service';
}
