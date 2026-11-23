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
    public function belongsTo(int $vehicleId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM vehicles WHERE vehicle_id = :v AND user_id = :u LIMIT 1');
        $stmt->execute(['v' => $vehicleId, 'u' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function plateExists(string $plate): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM vehicles WHERE plate_no = :p LIMIT 1');
        $stmt->execute(['p' => $plate]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(int $userId, array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicles (user_id, vehicle_type, make_model, plate_no, colour)
             VALUES (:u, :t, :m, :p, :c)'
        );
        $stmt->execute([
            'u' => $userId, 't' => $d['vehicle_type'], 'm' => $d['make_model'],
            'p' => $d['plate_no'], 'c' => $d['colour'] !== '' ? $d['colour'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
