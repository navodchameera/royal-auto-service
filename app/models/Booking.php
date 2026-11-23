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
             WHERE b.user_id = :u AND b.status = 'quoted'
             ORDER BY b.appointment_date"
        );
        $stmt->execute(['u' => $userId]);
        foreach ($stmt->fetchAll() as $r) {
            $items[] = ['title' => 'Quotation ready', 'meta' => '#' . $r['booking_id'] . ' · ' . money($r['quoted_price']), 'button' => 'Review'];
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
            $items[] = ['title' => 'Payment due', 'meta' => '#' . $r['booking_id'] . ($r['quoted_price'] !== null ? ' · ' . money($r['quoted_price']) : ''), 'button' => 'View'];
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
            $items[] = ['title' => 'How was your wash?', 'meta' => '#' . $r['booking_id'] . ' · ' . $r['make_model'], 'button' => 'Rate'];
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
}
