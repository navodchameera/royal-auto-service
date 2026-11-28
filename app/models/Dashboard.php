<?php
defined('APP_PATH') or exit;

// Queries for the manager dashboard
class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function counts(): array
    {
        $one = function (string $sql): int {
            return (int)$this->db->query($sql)->fetchColumn();
        };

        return [
            // customers waiting for a price
            'requests' => $one("SELECT COUNT(*) FROM bookings WHERE status = 'requested'"),
            // confirmed bookings (quotation sent, cleaner assigned) that have not started
            'upcoming' => $one("SELECT COUNT(*) FROM bookings WHERE status = 'assigned'"),
        ];
    }

    public function scheduleToday(): array
    {
        return $this->db->query(
            "SELECT b.booking_id, b.appointment_time, b.status, b.location_type,
                    v.make_model, p.package_name, cu.full_name AS cleaner_name
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN jobs j ON j.booking_id = b.booking_id
             LEFT JOIN users cu ON cu.user_id = j.cleaner_id
             WHERE b.appointment_date = CURDATE()
               AND b.status IN ('accepted','assigned','in_progress','completed','finished')
             ORDER BY b.appointment_time"
        )->fetchAll();
    }

    public function cleanersToday(): array
    {
        return $this->db->query(
            "SELECT u.user_id, u.full_name,
                    (SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id
                     WHERE j.cleaner_id = u.user_id AND b.appointment_date = CURDATE()
                       AND b.status <> 'cancelled') AS jobs_today,
                    (SELECT COUNT(*) FROM jobs j
                     WHERE j.cleaner_id = u.user_id
                       AND j.job_status IN ('on_the_way','arrived','in_progress')) AS busy
             FROM users u
             WHERE u.role = 'cleaner' AND u.status = 'active'
             ORDER BY u.full_name"
        )->fetchAll();
    }

    public function latestReviews(int $limit = 3): array
    {
        return $this->db->query(
            "SELECT r.review_id, r.booking_id, r.rating, r.comment, u.full_name
             FROM reviews r JOIN users u ON u.user_id = r.user_id
             ORDER BY r.review_date DESC, r.review_id DESC
             LIMIT " . (int)$limit
        )->fetchAll();
    }
}
