<?php
defined('APP_PATH') or exit;

// Everything printed on the bill of a finished booking
class Bill
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // $userId = 0 means "any customer" (the manager). Only finished bookings have a bill.
    public function forBooking(int $bookingId, int $userId): ?array
    {
        $sql = "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status, b.location_type, b.job_address, b.created_at,
                       u.full_name, u.email, u.phone, u.address AS cust_address,
                       v.make_model, v.plate_no, v.vehicle_type, v.colour,
                       p.package_name, p.description AS package_desc,
                       q.quoted_price, q.condition_level, q.remarks, q.quotation_date,
                       j.arrived_at, j.completed_at, cu.full_name AS cleaner_name,
                       pm.amount AS paid_amount, pm.method AS pay_method, pm.payment_date, pm.payment_id,
                       r.rating, r.comment AS review_comment
                FROM bookings b
                JOIN users u ON u.user_id = b.user_id
                JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                JOIN packages p ON p.package_id = b.package_id
                JOIN quotations q ON q.booking_id = b.booking_id
                LEFT JOIN jobs j ON j.booking_id = b.booking_id
                LEFT JOIN users cu ON cu.user_id = j.cleaner_id
                LEFT JOIN payments pm ON pm.payment_id = (SELECT MIN(p2.payment_id) FROM payments p2 WHERE p2.booking_id = b.booking_id AND p2.payment_status = 'paid')
                LEFT JOIN reviews r ON r.booking_id = b.booking_id
                WHERE b.booking_id = :b AND b.status = 'finished'";
        $params = ['b' => $bookingId];
        if ($userId > 0) {
            $sql .= " AND b.user_id = :u";
            $params['u'] = $userId;
        }
        $stmt = $this->db->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
