<?php
defined('APP_PATH') or exit;

// Payments: online (demo card form, by the customer) or cash (marked by manager / cleaner)
class Payment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // A confirmed, unpaid booking that belongs to this customer (or null)
    public function payable(int $bookingId, int $userId): ?array
    {
        $stmt = $this->db->prepare($this->dueSql() . " AND b.booking_id = :b");
        $stmt->execute(['u' => $userId, 'b' => $bookingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // All confirmed, unpaid bookings of this customer
    public function due(int $userId): array
    {
        $stmt = $this->db->prepare($this->dueSql() . " ORDER BY b.appointment_date, b.appointment_time");
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function history(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pay.payment_id, pay.amount, pay.method, pay.payment_date, b.booking_id, v.make_model
             FROM payments pay
             JOIN bookings b ON b.booking_id = pay.booking_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             WHERE b.user_id = :u AND pay.payment_status = 'paid'
             ORDER BY pay.payment_date DESC, pay.payment_id DESC"
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    // Records a paid payment for the quoted price. Returns '' on success or a message.
    // $customerId / $cleanerId (optional) make sure the booking belongs to that person.
    public function record(int $bookingId, string $method, array $allowedStatuses, ?int $customerId = null, ?int $cleanerId = null): string
    {
        try {
            $this->db->beginTransaction();

            $sql    = "SELECT b.status, q.quoted_price
                       FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id
                       WHERE b.booking_id = :b";
            $params = ['b' => $bookingId];
            if ($customerId !== null) {
                $sql .= " AND b.user_id = :c";
                $params['c'] = $customerId;
            }
            if ($cleanerId !== null) {
                $sql .= " AND EXISTS (SELECT 1 FROM jobs j WHERE j.booking_id = b.booking_id AND j.cleaner_id = :cl)";
                $params['cl'] = $cleanerId;
            }
            $stmt = $this->db->prepare($sql . " FOR UPDATE");
            $stmt->execute($params);
            $row = $stmt->fetch();

            if (!$row) {
                $this->db->rollBack();
                return 'Booking not found.';
            }
            if (!in_array($row['status'], $allowedStatuses, true)) {
                $this->db->rollBack();
                return 'This booking is not at a stage where it can be paid.';
            }

            $stmt = $this->db->prepare("SELECT 1 FROM payments WHERE booking_id = :b AND payment_status = 'paid' LIMIT 1");
            $stmt->execute(['b' => $bookingId]);
            if ($stmt->fetchColumn()) {
                $this->db->rollBack();
                return 'This booking is already paid.';
            }

            $stmt = $this->db->prepare(
                "INSERT INTO payments (booking_id, amount, method, payment_status, payment_date)
                 VALUES (:b, :a, :m, 'paid', NOW())"
            );
            $stmt->execute(['b' => $bookingId, 'a' => $row['quoted_price'], 'm' => $method]);

            $this->db->commit();
            return '';
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return 'Something went wrong while saving the payment. Please try again.';
        }
    }

    private function dueSql(): string
    {
        return "SELECT b.booking_id, b.status, b.appointment_date, b.appointment_time,
                       v.make_model, v.plate_no, p.package_name, q.quoted_price
                FROM bookings b
                JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                JOIN packages p ON p.package_id = b.package_id
                JOIN quotations q ON q.booking_id = b.booking_id
                WHERE b.user_id = :u
                  AND b.status IN ('assigned','in_progress','completed')
                  AND NOT EXISTS (SELECT 1 FROM payments pay
                                  WHERE pay.booking_id = b.booking_id AND pay.payment_status = 'paid')";
    }
}
