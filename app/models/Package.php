<?php
defined('APP_PATH') or exit;

class Package
{
    // Packages a customer can book (inactive ones are hidden)
    public function all(): array
    {
        $db = Database::connect();
        try {
            return $db->query('SELECT package_id, package_name, description, min_price, max_price FROM packages WHERE is_active = 1 ORDER BY package_id')->fetchAll();
        } catch (PDOException $ex) {
            // is_active column not added yet (database/packages_active_update.sql): offer every package
            return $db->query('SELECT package_id, package_name, description, min_price, max_price FROM packages ORDER BY package_id')->fetchAll();
        }
    }
}
