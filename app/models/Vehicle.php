<?php
defined('APP_PATH') or exit;

class Vehicle
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT vehicle_id, make_model, plate_no, vehicle_type, colour
             FROM vehicles WHERE user_id = :u ORDER BY vehicle_id'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }
}
