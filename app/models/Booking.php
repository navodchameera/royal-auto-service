<?php
defined('APP_PATH') or exit;

class Booking
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // The nearest confirmed appointment from today onwards
    public function nextAppointment(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.location_type, b.appointment_date, b.appointment_time, b.status,
                    v.make_model, v.plate_no, p.package_name
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             WHERE b.user_id = :u
               AND b.status IN ('accepted','assigned','in_progress')
               AND b.appointment_date >= CURDATE()
             ORDER BY b.appointment_date, b.appointment_time
             LIMIT 1"
        );
        $stmt->execute(['u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Things the customer has to act on: quotations, payments, reviews
    public function attention(int $userId): array
    {
        $items = [];

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, q.quoted_price
             FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.user_id = :u AND b.status = 'assigned'
             ORDER BY b.appointment_date"
        );
        $stmt->execute(['u' => $userId]);
        foreach ($stmt->fetchAll() as $r) {
            $items[] = ['title' => 'New quotation', 'meta' => '#' . $r['booking_id'] . ' · ' . money($r['quoted_price']), 'button' => 'View', 'href' => 'bookings'];
        }

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, q.quoted_price
             FROM bookings b LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.user_id = :u AND b.status = 'completed'
               AND NOT EXISTS (SELECT 1 FROM payments pay
                               WHERE pay.booking_id = b.booking_id AND pay.payment_status = 'paid')
             ORDER BY b.appointment_date"
        );
        $stmt->execute(['u' => $userId]);
        foreach ($stmt->fetchAll() as $r) {
            $items[] = ['title' => 'Payment due', 'meta' => '#' . $r['booking_id'] . ($r['quoted_price'] !== null ? ' · ' . money($r['quoted_price']) : ''), 'button' => 'Pay', 'href' => 'payments'];
        }

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, v.make_model
             FROM bookings b JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             WHERE b.user_id = :u AND b.status = 'completed'
               AND EXISTS (SELECT 1 FROM payments pay
                           WHERE pay.booking_id = b.booking_id AND pay.payment_status = 'paid')
               AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.booking_id)
             ORDER BY b.appointment_date DESC"
        );
        $stmt->execute(['u' => $userId]);
        foreach ($stmt->fetchAll() as $r) {
            $items[] = ['title' => 'How was your wash?', 'meta' => '#' . $r['booking_id'] . ' · ' . $r['make_model'], 'button' => 'Rate', 'href' => '#'];
        }

        return $items;
    }

    public function recent(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.status,
                    v.make_model, p.package_name, q.quoted_price
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.user_id = :u
             ORDER BY b.appointment_date DESC, b.booking_id DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }
    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bookings (user_id, vehicle_id, package_id, location_type, job_address, appointment_date, appointment_time)
             VALUES (:u, :v, :p, :l, :a, :d, :t)'
        );
        $stmt->execute([
            'u' => $d['user_id'], 'v' => $d['vehicle_id'], 'p' => $d['package_id'],
            'l' => $d['location_type'], 'a' => $d['job_address'],
            'd' => $d['appointment_date'], 't' => $d['appointment_time'] . ':00',
        ]);
        return (int)$this->db->lastInsertId();
    }
    // Bookings for the "My bookings" page. $filter is one of: all, active, completed
    public function listForUser(int $userId, string $filter): array
    {
        $where = [
            'all'       => '',
            'active'    => " AND b.status IN ('requested','quoted','accepted','assigned','in_progress')",
            'completed' => " AND b.status = 'completed'",
        ][$filter] ?? '';

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.location_type, b.job_address, b.appointment_date, b.appointment_time, b.status,
                    v.make_model, v.plate_no, p.package_name,
                    q.quoted_price, q.condition_level, q.remarks,
                    (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_method,
                    (SELECT COUNT(*) FROM photos ph WHERE ph.booking_id = b.booking_id) AS photo_count
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.user_id = :u" . $where . "
             ORDER BY b.appointment_date DESC, b.appointment_time DESC, b.booking_id DESC"
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }



    // Cancel before the work has started
    public function cancel(int $bookingId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled'
             WHERE booking_id = :b AND user_id = :u
               AND status IN ('requested','quoted','accepted','assigned')"
        );
        $stmt->execute(['b' => $bookingId, 'u' => $userId]);
        return $stmt->rowCount() === 1;
    }

}
