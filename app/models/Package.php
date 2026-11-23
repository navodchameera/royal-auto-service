<?php
defined('APP_PATH') or exit;

class Package
{
    public function all(): array
    {
        return Database::connect()
            ->query('SELECT package_id, package_name, description, min_price, max_price FROM packages ORDER BY package_id')
            ->fetchAll();
    }
}
