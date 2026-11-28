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
                       u.full_name, v.make_model, v.plate_no, p.package_name, qt.quoted_price, qt.status AS q_status,
                       (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_method
                FROM bookings b
                JOIN users u ON u.user_id = b.user_id
                JOIN vehicles v ON v.vehicle_id = b.vehicle_id
                JOIN packages p ON p.package_id = b.package_id
                LEFT JOIN quotations qt ON qt.booking_id = b.booking_id
                WHERE 1 = 1";
        $params = [];
        if ($status === 'active') {
            $sql .= " AND b.status IN ('requested','quoted','accepted','assigned','in_progress','completed')";
        } elseif ($status === 'rejected') {
            $sql .= " AND b.status = 'cancelled' AND qt.status = 'rejected'";
        } elseif ($status === 'cancelled') {
            $sql .= " AND b.status = 'cancelled' AND (qt.status IS NULL OR qt.status <> 'rejected')";
        } elseif ($status !== 'all') {
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
                    qt.quoted_price, qt.condition_level, qt.remarks, qt.status AS q_status, qt.quotation_date,
                    cu.full_name AS cleaner_name, j.cleaner_id, j.arrived_at, j.completed_at,
                    (SELECT pm.method FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_method,
                    (SELECT pm.payment_date FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status = 'paid' ORDER BY pm.payment_id LIMIT 1) AS pay_date
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

    // Rejects a booking that is still waiting for a quotation. The reason is kept in the quotation
    // remarks (quotation status "rejected", price 0) and the booking is closed as cancelled.
    // Returns '' on success or a message.
    public function rejectBooking(int $id, string $reason): string
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT status FROM bookings WHERE booking_id = :b FOR UPDATE');
            $stmt->execute(['b' => $id]);
            if ($stmt->fetchColumn() !== 'requested') {
                $this->db->rollBack();
                return 'Only a booking that is waiting for a quotation can be rejected.';
            }

            $stmt = $this->db->prepare(
                "INSERT INTO quotations (booking_id, quoted_price, condition_level, remarks, status, quotation_date)
                 VALUES (:b, 0, NULL, :r, 'rejected', NOW())
                 ON DUPLICATE KEY UPDATE quoted_price = 0, condition_level = NULL, remarks = :r2, status = 'rejected', quotation_date = NOW()"
            );
            $stmt->execute(['b' => $id, 'r' => $reason, 'r2' => $reason]);

            $stmt = $this->db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :b");
            $stmt->execute(['b' => $id]);

            $this->db->commit();
            return '';
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return 'The booking could not be rejected. Please try again.';
        }
    }

    // ---------------------------------------------------------- quotations
    public function quoteQueue(string $filter): array
    {
        $cond = $filter === 'sent'
            ? "b.status = 'assigned' AND EXISTS (SELECT 1 FROM quotations qt WHERE qt.booking_id = b.booking_id)"
            : "b.status = 'requested'";
        $stmt = $this->db->prepare(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status,
                    u.full_name, v.make_model, v.plate_no, p.package_name,
                    (SELECT COUNT(*) FROM photos ph WHERE ph.booking_id = b.booking_id) AS photo_count
             FROM bookings b
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             WHERE " . $cond . "
             ORDER BY b.created_at, b.booking_id"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Active cleaners for one booking's date and time, with how many other jobs clash with it
    public function cleanersFor(int $bookingId, string $date, string $time): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.full_name,
                    (SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id
                     WHERE j.cleaner_id = u.user_id AND b.booking_id <> :bid AND b.appointment_date = :d
                       AND b.status IN ('quoted','accepted','assigned','in_progress')
                       AND ABS(TIME_TO_SEC(TIMEDIFF(b.appointment_time, :t))) < " . (int)(JOB_HOURS * 3600) . ") AS conflicts
             FROM users u
             WHERE u.role = 'cleaner' AND u.status = 'active'
             ORDER BY u.full_name"
        );
        $stmt->execute(['bid' => $bookingId, 'd' => $date, 't' => $time]);
        return $stmt->fetchAll();
    }

    private function cleanerFree(int $cleanerId, int $bookingId, string $date, string $time): bool
    {
        foreach ($this->cleanersFor($bookingId, $date, $time) as $c) {
            if ((int)$c['user_id'] === $cleanerId) { return (int)$c['conflicts'] === 0; }
        }
        return false;   // not an active cleaner
    }

    // Saves the quotation, assigns the chosen cleaner and confirms the booking (status "assigned").
    // Returns '' on success, or a message to show the manager.
    public function saveQuotation(int $bookingId, string $price, string $level, string $remarks, int $cleanerId): string
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT status, appointment_date, appointment_time FROM bookings WHERE booking_id = :b FOR UPDATE');
            $stmt->execute(['b' => $bookingId]);
            $row = $stmt->fetch();
            if (!$row || !in_array($row['status'], ['requested', 'assigned'], true)) {
                $this->db->rollBack();
                return 'This booking can no longer be quoted. It may have been cancelled.';
            }
            if (!$this->cleanerFree($cleanerId, $bookingId, $row['appointment_date'], $row['appointment_time'])) {
                $this->db->rollBack();
                return 'That cleaner is not free at this date and time. Please choose another cleaner.';
            }

            $stmt = $this->db->prepare(
                "INSERT INTO quotations (booking_id, quoted_price, condition_level, remarks, status, quotation_date)
                 VALUES (:b, :p, :c, :r, 'sent', NOW())
                 ON DUPLICATE KEY UPDATE quoted_price = :p2, condition_level = :c2, remarks = :r2,
                                         status = 'sent', quotation_date = NOW()"
            );
            $r = $remarks !== '' ? $remarks : null;
            $stmt->execute(['b' => $bookingId, 'p' => $price, 'c' => $level, 'r' => $r, 'p2' => $price, 'c2' => $level, 'r2' => $r]);

            $stmt = $this->db->prepare(
                "INSERT INTO jobs (booking_id, cleaner_id, job_status) VALUES (:b, :c, 'assigned')
                 ON DUPLICATE KEY UPDATE cleaner_id = :c2, job_status = 'assigned'"
            );
            $stmt->execute(['b' => $bookingId, 'c' => $cleanerId, 'c2' => $cleanerId]);

            $stmt = $this->db->prepare("UPDATE bookings SET status = 'assigned' WHERE booking_id = :b");
            $stmt->execute(['b' => $bookingId]);

            $this->db->commit();
            return '';
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return 'Something went wrong while saving. Please try again.';
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

    public function setUserStatus(int $id, string $role, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :s WHERE user_id = :id AND role = :r");
        $stmt->execute(['s' => $status, 'id' => $id, 'r' => $role]);
        return $stmt->rowCount() === 1;
    }

    public function userByRole(int $id, string $role): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :id AND role = :r LIMIT 1");
        $stmt->execute(['id' => $id, 'r' => $role]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Permanently deletes a customer or cleaner (the database cascades to their records).
    // Returns ['ok' => bool, 'error' => string, 'files' => photo file names to remove from disk]
    public function deleteUser(int $id, string $role): array
    {
        try {
            $this->db->beginTransaction();

            if ($role === 'cleaner') {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id
                     WHERE j.cleaner_id = :id AND b.status IN ('assigned','in_progress')"
                );
                $stmt->execute(['id' => $id]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $this->db->rollBack();
                    return ['ok' => false, 'files' => [], 'error' => 'This cleaner still has upcoming jobs. Update those quotations to another cleaner, or cancel the bookings, first.'];
                }
            }

            // Remember the customer's photo files, because the rows vanish with the cascade
            $stmt = $this->db->prepare(
                "SELECT ph.file_path FROM photos ph JOIN bookings b ON b.booking_id = ph.booking_id WHERE b.user_id = :id"
            );
            $stmt->execute(['id' => $id]);
            $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = :id AND role = :r");
            $stmt->execute(['id' => $id, 'r' => $role]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['ok' => false, 'files' => [], 'error' => 'User not found.'];
            }

            $this->db->commit();
            return ['ok' => true, 'files' => $files, 'error' => ''];
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return ['ok' => false, 'files' => [], 'error' => 'The user could not be deleted because other records still depend on them. Run the cascade version of the database script and try again.'];
        }
    }
    // ------------------------------------------------------------ cleaners
    public function cleaners(): array
    {
        return $this->db->query(
            "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.address, u.status, u.created_at,
                    (SELECT COUNT(*) FROM jobs j WHERE j.cleaner_id = u.user_id) AS jobs_total
             FROM users u WHERE u.role = 'cleaner'
             ORDER BY u.full_name"
        )->fetchAll();
    }

    public function createCleaner(array $d, string $password): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, username, email, phone, password, address, role, status)
             VALUES (:n, :u, :e, :p, :pw, :a, 'cleaner', 'active')"
        );
        $stmt->execute([
            'n' => $d['full_name'], 'u' => $d['username'], 'e' => $d['email'], 'p' => $d['phone'],
            'pw' => password_hash($password, PASSWORD_DEFAULT), 'a' => $d['address'],
        ]);
        return (int)$this->db->lastInsertId();
    }
}
