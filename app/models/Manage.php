<?php
defined('APP_PATH') or exit;

// Queries used by the manager's Bookings, Quotations and Customers pages
class Manage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ------------------------------------------------------------ bookings
    public function bookings(string $status, string $q): array
    {
        $sql = "SELECT b.booking_id, b.location_type, b.appointment_date, b.appointment_time, b.status,
                       u.full_name, v.make_model, v.plate_no, p.package_name, qt.quoted_price
                FROM bookings b
                JOIN users u ON u.user_id = b.user_id
                JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                JOIN packages p ON p.package_id = b.package_id
                LEFT JOIN quotations qt ON qt.booking_id = b.booking_id
                WHERE 1 = 1";
        $params = [];
        if ($status !== 'all') {
            $sql .= " AND b.status = :s";
            $params['s'] = $status;
        }
        if ($q !== '') {
            $sql .= " AND (u.full_name LIKE :q1 OR u.username LIKE :q2 OR v.plate_no LIKE :q3
                           OR v.make_model LIKE :q4 OR b.booking_id = :qid)";
            $like = '%' . $q . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'qid' => (int)ltrim($q, '#')];
        }
        $sql .= " ORDER BY b.appointment_date DESC, b.appointment_time DESC, b.booking_id DESC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function booking(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.location_type, b.job_address, b.appointment_date, b.appointment_time, b.status, b.created_at,
                    u.user_id AS cust_id, u.full_name, u.email, u.phone, u.address AS cust_address,
                    v.make_model, v.plate_no, v.colour, v.vehicle_type,
                    p.package_name, p.min_price, p.max_price,
                    qt.quoted_price, qt.condition_level, qt.remarks, qt.status AS q_status,
                    cu.full_name AS cleaner_name
             FROM bookings b
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations qt ON qt.booking_id = b.booking_id
             LEFT JOIN jobs j ON j.booking_id = b.booking_id
             LEFT JOIN users cu ON cu.user_id = j.cleaner_id
             WHERE b.booking_id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function photos(int $bookingId): array
    {
        $stmt = $this->db->prepare('SELECT file_path, photo_area FROM photos WHERE booking_id = :b ORDER BY photo_id');
        $stmt->execute(['b' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function cancelBooking(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled'
             WHERE booking_id = :id AND status IN ('requested','quoted','accepted','assigned')"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1;
    }

    // ---------------------------------------------------------- quotations
    public function quoteQueue(string $status): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status,
                    u.full_name, v.make_model, v.plate_no, p.package_name,
                    (SELECT COUNT(*) FROM photos ph WHERE ph.booking_id = b.booking_id) AS photo_count
             FROM bookings b
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             WHERE b.status = :s
             ORDER BY b.created_at, b.booking_id"
        );
        $stmt->execute(['s' => $status]);
        return $stmt->fetchAll();
    }

    // Creates or replaces the quotation and marks the booking "quoted"
    public function saveQuotation(int $bookingId, string $price, string $level, string $remarks): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT status FROM bookings WHERE booking_id = :b FOR UPDATE');
            $stmt->execute(['b' => $bookingId]);
            $status = $stmt->fetchColumn();
            if (!in_array($status, ['requested', 'quoted'], true)) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO quotations (booking_id, quoted_price, condition_level, remarks, status, quotation_date)
                 VALUES (:b, :p, :c, :r, 'sent', NOW())
                 ON DUPLICATE KEY UPDATE quoted_price = :p2, condition_level = :c2, remarks = :r2,
                                         status = 'sent', quotation_date = NOW()"
            );
            $stmt->execute([
                'b' => $bookingId, 'p' => $price, 'c' => $level, 'r' => $remarks !== '' ? $remarks : null,
                'p2' => $price, 'c2' => $level, 'r2' => $remarks !== '' ? $remarks : null,
            ]);

            $stmt = $this->db->prepare("UPDATE bookings SET status = 'quoted' WHERE booking_id = :b");
            $stmt->execute(['b' => $bookingId]);

            $this->db->commit();
            return true;
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return false;
        }
    }

    // ----------------------------------------------------------- customers
    public function customers(string $status, string $q): array
    {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.status, u.created_at,
                       (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) AS bookings
                FROM users u WHERE u.role = 'customer'";
        $params = [];
        if ($status !== 'all') {
            $sql .= " AND u.status = :s";
            $params['s'] = $status;
        }
        if ($q !== '') {
            $sql .= " AND (u.full_name LIKE :q1 OR u.username LIKE :q2 OR u.email LIKE :q3 OR u.phone LIKE :q4)";
            $like = '%' . $q . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $sql .= " ORDER BY u.created_at DESC, u.user_id DESC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function customer(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :id AND role = 'customer' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function customerVehicles(int $id): array
    {
        $stmt = $this->db->prepare('SELECT make_model, plate_no, vehicle_type, colour FROM vehicles WHERE user_id = :u ORDER BY vehicle_id');
        $stmt->execute(['u' => $id]);
        return $stmt->fetchAll();
    }

    public function customerBookings(int $id, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.status, v.make_model, p.package_name, qt.quoted_price
             FROM bookings b
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations qt ON qt.booking_id = b.booking_id
             WHERE b.user_id = :u
             ORDER BY b.appointment_date DESC, b.booking_id DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute(['u' => $id]);
        return $stmt->fetchAll();
    }

    public function customerActiveBookings(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings WHERE user_id = :u
             AND status IN ('requested','quoted','accepted','assigned','in_progress')"
        );
        $stmt->execute(['u' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public function emailTaken(string $email, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :e AND user_id <> :id LIMIT 1');
        $stmt->execute(['e' => $email, 'id' => $exceptId]);
        return (bool)$stmt->fetchColumn();
    }

    public function updateCustomer(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET full_name = :n, email = :e, phone = :p, address = :a
             WHERE user_id = :id AND role = 'customer'"
        );
        $stmt->execute(['n' => $d['full_name'], 'e' => $d['email'], 'p' => $d['phone'], 'a' => $d['address'], 'id' => $id]);
    }

    public function setCustomerStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :s WHERE user_id = :id AND role = 'customer'");
        $stmt->execute(['s' => $status, 'id' => $id]);
        return $stmt->rowCount() === 1;
    }
}
