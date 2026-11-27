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

    public function forCleaner(int $cleanerId): array
    {
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
             WHERE j.cleaner_id = :c AND b.status IN ('assigned','in_progress','completed')
             ORDER BY b.appointment_date, b.appointment_time
             LIMIT 200"
        );
        $stmt->execute(['c' => $cleanerId]);
        return $stmt->fetchAll();
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

    public function complete(int $bookingId, int $cleanerId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings b JOIN jobs j ON j.booking_id = b.booking_id
             SET b.status = 'completed', j.job_status = 'completed', j.completed_at = NOW()
             WHERE b.booking_id = :b AND j.cleaner_id = :c AND b.status = 'in_progress'"
        );
        $stmt->execute(['b' => $bookingId, 'c' => $cleanerId]);
        return $stmt->rowCount() > 0;
    }
}
