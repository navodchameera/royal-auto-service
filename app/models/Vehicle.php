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

    public function plateExists(string $plate, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM vehicles WHERE plate_no = :p AND vehicle_id <> :id LIMIT 1');
        $stmt->execute(['p' => $plate, 'id' => $exceptId]);
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
    // Vehicles with the number of bookings made for each
    public function forUserWithCounts(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.vehicle_id, v.make_model, v.plate_no, v.vehicle_type, v.colour,
                    (SELECT COUNT(*) FROM bookings b WHERE b.vehicle_id = v.vehicle_id) AS bookings,
                    (SELECT COUNT(*) FROM bookings b WHERE b.vehicle_id = v.vehicle_id
                       AND b.status IN ('requested','quoted','accepted','assigned','in_progress')) AS active_bookings
             FROM vehicles v WHERE v.user_id = :u ORDER BY v.vehicle_id"
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $vehicleId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT vehicle_id, vehicle_type, make_model, plate_no, colour
             FROM vehicles WHERE vehicle_id = :v AND user_id = :u LIMIT 1'
        );
        $stmt->execute(['v' => $vehicleId, 'u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $vehicleId, int $userId, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicles SET vehicle_type = :t, make_model = :m, plate_no = :p, colour = :c
             WHERE vehicle_id = :v AND user_id = :u'
        );
        $stmt->execute([
            't' => $d['vehicle_type'], 'm' => $d['make_model'], 'p' => $d['plate_no'],
            'c' => $d['colour'] !== '' ? $d['colour'] : null, 'v' => $vehicleId, 'u' => $userId,
        ]);
    }

    // True while the vehicle is part of a booking that is not completed or cancelled
    public function hasActiveBooking(int $vehicleId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM bookings WHERE vehicle_id = :v
             AND status IN ('requested','quoted','accepted','assigned','in_progress') LIMIT 1"
        );
        $stmt->execute(['v' => $vehicleId]);
        return (bool)$stmt->fetchColumn();
    }

    public function hasBookings(int $vehicleId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM bookings WHERE vehicle_id = :v LIMIT 1');
        $stmt->execute(['v' => $vehicleId]);
        return (bool)$stmt->fetchColumn();
    }

    public function delete(int $vehicleId, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM vehicles WHERE vehicle_id = :v AND user_id = :u');
        $stmt->execute(['v' => $vehicleId, 'u' => $userId]);
    }
}
