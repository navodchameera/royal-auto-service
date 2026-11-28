<?php
defined('APP_PATH') or exit;

// Queries for the manager's Jobs, Payments, Reviews and Packages pages
class ManageMore
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ---------------------------------------------------------------- jobs
    // $filter: all, today, active (assigned / in progress), done (job done or completed), cancelled
    public function jobs(string $filter): array
    {
        $map = [
            'all'       => '1 = 1',
            'today'     => "b.appointment_date = CURDATE() AND b.status <> 'cancelled'",
            'active'    => "b.status IN ('assigned','in_progress')",
            'done'      => "b.status IN ('completed','finished')",
            'cancelled' => "b.status = 'cancelled'",
        ];
        $stmt = $this->db->query(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status, b.location_type,
                    cu.full_name AS cleaner_name, c.full_name AS customer_name, v.make_model, v.plate_no,
                    p.package_name, j.arrived_at, j.completed_at,
                    (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_method
             FROM jobs j
             JOIN bookings b ON b.booking_id = j.booking_id
             JOIN users cu ON cu.user_id = j.cleaner_id
             JOIN users c ON c.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             WHERE " . ($map[$filter] ?? $map['all']) . "
             ORDER BY b.appointment_date DESC, b.appointment_time DESC LIMIT 300"
        );
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------ payments
    public function paymentTotals(): array
    {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(amount), 0) AS total,
                    COALESCE(SUM(CASE WHEN method = 'online' THEN amount END), 0) AS online,
                    COALESCE(SUM(CASE WHEN method = 'cash' THEN amount END), 0) AS cash,
                    COUNT(*) AS n
             FROM payments WHERE payment_status = 'paid'"
        )->fetch();
        $row['unpaid'] = (float)$this->db->query(
            "SELECT COALESCE(SUM(q.quoted_price), 0) FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.status IN ('assigned','in_progress','completed')
               AND NOT EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid')"
        )->fetchColumn();
        return $row;
    }

    // $filter: all, online, cash (paid payments) or unpaid (confirmed bookings not paid yet)
    public function payments(string $filter): array
    {
        if ($filter === 'unpaid') {
            return $this->db->query(
                "SELECT b.booking_id, b.status, b.appointment_date, u.full_name, v.make_model, q.quoted_price AS amount,
                        NULL AS method, NULL AS payment_date
                 FROM bookings b
                 JOIN quotations q ON q.booking_id = b.booking_id
                 JOIN users u ON u.user_id = b.user_id
                 JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                 WHERE b.status IN ('assigned','in_progress','completed')
                   AND NOT EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid')
                 ORDER BY b.appointment_date, b.appointment_time"
            )->fetchAll();
        }
        $where = in_array($filter, ['online', 'cash'], true) ? " AND pay.method = " . $this->db->quote($filter) : '';
        return $this->db->query(
            "SELECT b.booking_id, b.status, b.appointment_date, u.full_name, v.make_model, pay.amount, pay.method, pay.payment_date
             FROM payments pay
             JOIN bookings b ON b.booking_id = pay.booking_id
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             WHERE pay.payment_status = 'paid'" . $where . "
             ORDER BY pay.payment_date DESC, pay.payment_id DESC LIMIT 300"
        )->fetchAll();
    }

    // ------------------------------------------------------------- reviews
    public function reviewStats(): array
    {
        $row = $this->db->query("SELECT COUNT(*) AS n, COALESCE(AVG(rating), 0) AS avg FROM reviews")->fetch();
        $dist = array_fill(1, 5, 0);
        foreach ($this->db->query("SELECT rating, COUNT(*) AS n FROM reviews GROUP BY rating")->fetchAll() as $r) {
            $dist[(int)$r['rating']] = (int)$r['n'];
        }
        return ['n' => (int)$row['n'], 'avg' => (float)$row['avg'], 'dist' => $dist];
    }

    public function reviews(int $rating): array
    {
        $sql = "SELECT r.review_id, r.booking_id, r.rating, r.comment, r.review_date,
                       u.full_name, cu.full_name AS cleaner_name, p.package_name
                FROM reviews r
                JOIN users u ON u.user_id = r.user_id
                JOIN bookings b ON b.booking_id = r.booking_id
                JOIN packages p ON p.package_id = b.package_id
                LEFT JOIN jobs j ON j.booking_id = b.booking_id
                LEFT JOIN users cu ON cu.user_id = j.cleaner_id";
        if ($rating >= 1 && $rating <= 5) { $sql .= ' WHERE r.rating = ' . (int)$rating; }
        return $this->db->query($sql . " ORDER BY r.review_date DESC, r.review_id DESC LIMIT 300")->fetchAll();
    }

    // ------------------------------------------------------------ packages
    public function packages(): array
    {
        try {
            return $this->db->query(
                "SELECT p.package_id, p.package_name, p.description, p.min_price, p.max_price, p.is_active,
                        (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.package_id) AS bookings
                 FROM packages p ORDER BY p.package_id"
            )->fetchAll();
        } catch (PDOException $ex) {
            return $this->db->query(
                "SELECT p.package_id, p.package_name, p.description, p.min_price, p.max_price, 1 AS is_active,
                        (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.package_id) AS bookings
                 FROM packages p ORDER BY p.package_id"
            )->fetchAll();
        }
    }

    // Makes a package active or inactive. Returns '' or a message.
    public function setPackageActive(int $id, bool $active): string
    {
        try {
            if (!$active) {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM packages WHERE is_active = 1 AND package_id <> :id');
                $stmt->execute(['id' => $id]);
                if ((int)$stmt->fetchColumn() === 0) {
                    return 'At least one package must stay active, so customers can still book.';
                }
            }
            $stmt = $this->db->prepare('UPDATE packages SET is_active = :a WHERE package_id = :id');
            $stmt->execute(['a' => $active ? 1 : 0, 'id' => $id]);
            return '';
        } catch (PDOException $ex) {
            return 'The database needs one update first. Run database/packages_active_update.sql in phpMyAdmin.';
        }
    }

    // Deletes a package nobody has booked. A package with bookings cannot be deleted, because the
    // database would delete those bookings (and their payments) with it. Make it inactive instead.
    public function deletePackage(int $id): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bookings WHERE package_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return 'This package has bookings, so deleting it would also delete those bookings and their payments. Make it inactive instead.';
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM packages WHERE package_id <> :id' . $this->activeClause());
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() === 0) {
            return 'You cannot delete the last package.';
        }
        $stmt = $this->db->prepare('DELETE FROM packages WHERE package_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1 ? '' : 'Package not found.';
    }

    private function activeClause(): string
    {
        try {
            $this->db->query('SELECT is_active FROM packages LIMIT 1');
            return ' AND is_active = 1';
        } catch (PDOException $ex) {
            return '';
        }
    }

    public function nameTaken(string $name, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM packages WHERE package_name = :n AND package_id <> :id LIMIT 1');
        $stmt->execute(['n' => $name, 'id' => $exceptId]);
        return (bool)$stmt->fetchColumn();
    }

    public function savePackage(int $id, array $d): void
    {
        if ($id > 0) {
            $stmt = $this->db->prepare('UPDATE packages SET package_name = :n, description = :d, min_price = :lo, max_price = :hi WHERE package_id = :id');
            $stmt->execute(['n' => $d['name'], 'd' => $d['description'] !== '' ? $d['description'] : null, 'lo' => $d['min'], 'hi' => $d['max'], 'id' => $id]);
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO packages (package_name, description, min_price, max_price) VALUES (:n, :d, :lo, :hi)');
        $stmt->execute(['n' => $d['name'], 'd' => $d['description'] !== '' ? $d['description'] : null, 'lo' => $d['min'], 'hi' => $d['max']]);
    }
}
