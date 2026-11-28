<?php
defined('APP_PATH') or exit;

// The customer's bookings: list, cancel, review, finish
class MyBookings
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // $filter: all, active (everything not finished), completed (finished), cancelled
    public function listForUser(int $userId, string $filter): array
    {
        $map = [
            'all'       => '',
            'active'    => " AND b.status IN ('requested','quoted','accepted','assigned','in_progress','completed')",
            'completed' => " AND b.status = 'finished'",
            'cancelled' => " AND b.status = 'cancelled'",
        ];
        $where = $map[$filter] ?? '';

        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.location_type, b.job_address, b.appointment_date, b.appointment_time, b.status,
                    v.make_model, v.plate_no, p.package_name,
                    q.quoted_price, q.condition_level, q.remarks, q.status AS q_status,
                    (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid'
                     ORDER BY pm.payment_id LIMIT 1) AS pay_method,
                    (SELECT COUNT(*) FROM photos ph WHERE ph.booking_id = b.booking_id) AS photo_count,
                    r.rating AS review_rating, r.comment AS review_comment
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             LEFT JOIN reviews r ON r.booking_id = b.booking_id
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

    // Saves the rating and review once the cleaner has completed the job. Returns '' or a message.
    public function review(int $bookingId, int $userId, int $rating, string $comment): string
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT status FROM bookings WHERE booking_id = :b AND user_id = :u FOR UPDATE');
            $stmt->execute(['b' => $bookingId, 'u' => $userId]);
            if ($stmt->fetchColumn() !== 'completed') {
                $this->db->rollBack();
                return 'You can review a booking once the cleaner has completed the job.';
            }

            $stmt = $this->db->prepare('SELECT 1 FROM reviews WHERE booking_id = :b');
            $stmt->execute(['b' => $bookingId]);
            if ($stmt->fetchColumn()) {
                $this->db->rollBack();
                return 'You have already reviewed this booking.';
            }

            $stmt = $this->db->prepare('INSERT INTO reviews (booking_id, user_id, rating, comment) VALUES (:b, :u, :r, :c)');
            $stmt->execute(['b' => $bookingId, 'u' => $userId, 'r' => $rating, 'c' => $comment]);

            $this->db->commit();
            return '';
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return 'Your review could not be saved. Please try again.';
        }
    }

    // The customer confirms: the booking is fully completed (needs a review first)
    public function finish(int $bookingId, int $userId): string
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'finished'
                 WHERE booking_id = :b AND user_id = :u AND status = 'completed'
                   AND EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = bookings.booking_id)"
            );
            $stmt->execute(['b' => $bookingId, 'u' => $userId]);
            return $stmt->rowCount() === 1 ? '' : 'Please rate and review the service first, then complete the booking.';
        } catch (Throwable $ex) {
            return 'The database needs one update before bookings can be completed. Run database/finish_status_update.sql in phpMyAdmin.';
        }
    }

    // Things the customer has to act on (shown on the home page)
    public function attention(int $userId): array
    {
        $items = [];

        $q = function (string $sql) use ($userId): array {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['u' => $userId]);
            return $stmt->fetchAll();
        };

        foreach ($q("SELECT b.booking_id, qt.quoted_price FROM bookings b
                     JOIN quotations qt ON qt.booking_id = b.booking_id
                     WHERE b.user_id = :u AND b.status = 'assigned' ORDER BY b.appointment_date") as $r) {
            $items[] = ['title' => 'New quotation', 'meta' => '#' . $r['booking_id'] . ' · ' . money($r['quoted_price']), 'button' => 'View', 'href' => 'bookings&filter=active'];
        }

        foreach ($q("SELECT b.booking_id, qt.quoted_price FROM bookings b
                     JOIN quotations qt ON qt.booking_id = b.booking_id
                     WHERE b.user_id = :u AND b.status = 'in_progress'
                       AND NOT EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid')
                     ORDER BY b.appointment_date") as $r) {
            $items[] = ['title' => 'Payment due', 'meta' => '#' . $r['booking_id'] . ' · ' . money($r['quoted_price']), 'button' => 'Pay', 'href' => 'payments'];
        }

        foreach ($q("SELECT b.booking_id, v.make_model,
                            EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.booking_id) AS reviewed
                     FROM bookings b JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                     WHERE b.user_id = :u AND b.status = 'completed' ORDER BY b.appointment_date DESC") as $r) {
            $items[] = $r['reviewed']
                ? ['title' => 'Ready to complete', 'meta' => '#' . $r['booking_id'] . ' · ' . $r['make_model'], 'button' => 'Complete', 'href' => 'bookings&filter=active']
                : ['title' => 'Job done, please review', 'meta' => '#' . $r['booking_id'] . ' · ' . $r['make_model'], 'button' => 'Review', 'href' => 'bookings&filter=active'];
        }

        return $items;
    }
}
