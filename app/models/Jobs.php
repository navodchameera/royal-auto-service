<?php
defined('APP_PATH') or exit;

// A cleaner's own jobs
class Jobs
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // $filter: all, active (assigned / in progress), completed (done / finished), cancelled
    public function forCleaner(int $cleanerId, string $filter = 'all'): array
    {
        $map = [
            'all'       => "b.status IN ('assigned','in_progress','completed','finished','cancelled')",
            'active'    => "b.status IN ('assigned','in_progress')",
            'completed' => "b.status IN ('completed','finished')",
            'cancelled' => "b.status = 'cancelled'",
        ];
        $where = $map[$filter] ?? $map['all'];
        $order = $filter === 'active' ? 'ASC' : 'DESC';

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status, b.location_type, b.job_address,
                    u.full_name, u.phone, v.make_model, v.plate_no, p.package_name, q.quoted_price,
                    (SELECT pm.method FROM payments pm
                     WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid'
                     ORDER BY pm.payment_id LIMIT 1) AS pay_method
             FROM jobs j
             JOIN bookings b ON b.booking_id = j.booking_id
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE j.cleaner_id = :c AND " . $where . "
             ORDER BY b.appointment_date " . $order . ", b.appointment_time " . $order . "
             LIMIT 300"
        );
        $stmt->execute(['c' => $cleanerId]);
        return $stmt->fetchAll();
    }

    // One job with every detail of its booking. Only returns it when it belongs to this cleaner.
    public function detail(int $bookingId, int $cleanerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status, b.location_type, b.job_address, b.created_at,
                    u.full_name, u.phone, u.email, u.address AS cust_address,
                    v.make_model, v.plate_no, v.colour, v.vehicle_type,
                    p.package_name, p.description AS package_desc,
                    q.quoted_price, q.condition_level, q.remarks, q.quotation_date,
                    j.arrived_at, j.completed_at,
                    (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_method,
                    (SELECT pm.payment_date FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_date
             FROM jobs j
             JOIN bookings b ON b.booking_id = j.booking_id
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.booking_id = :b AND j.cleaner_id = :c
             LIMIT 1"
        );
        $stmt->execute(['b' => $bookingId, 'c' => $cleanerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function start(int $bookingId, int $cleanerId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings b JOIN jobs j ON j.booking_id = b.booking_id
             SET b.status = 'in_progress', j.job_status = 'in_progress', j.arrived_at = NOW()
             WHERE b.booking_id = :b AND j.cleaner_id = :c AND b.status = 'assigned'"
        );
        $stmt->execute(['b' => $bookingId, 'c' => $cleanerId]);
        return $stmt->rowCount() > 0;
    }

    // A job can only be completed after it has been paid (online by the customer, or cash by the cleaner)
    public function complete(int $bookingId, int $cleanerId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings b JOIN jobs j ON j.booking_id = b.booking_id
             SET b.status = 'completed', j.job_status = 'completed', j.completed_at = NOW()
             WHERE b.booking_id = :b AND j.cleaner_id = :c AND b.status = 'in_progress'
               AND EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid')"
        );
        $stmt->execute(['b' => $bookingId, 'c' => $cleanerId]);
        return $stmt->rowCount() > 0;
    }
}
