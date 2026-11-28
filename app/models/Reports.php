<?php
defined('APP_PATH') or exit;

// Business reports for the manager.
//
// Every report is built as plain data, so the same result can be shown on the page, printed,
// downloaded as CSV and downloaded as PDF:
//   ['title' => string, 'basis' => string, 'kpis' => [[label, value, type, note]],
//    'insights' => [string], 'sections' => [['title', 'cols' => [[label, type]], 'rows' => [[...]], 'bar' => colIndex|null, 'note' => string]]]
// Column types: text, int, money, pct, dec, date, dt
//
// Bookings are counted by their appointment date, earnings by the day the payment was made,
// new customers by the day they registered and ratings by the day they were written.
class Reports
{
    public const TYPES = [
        'overview'  => 'Business summary',
        'bookings'  => 'Bookings',
        'earnings'  => 'Earnings',
        'services'  => 'Services',
        'cleaners'  => 'Cleaners',
        'customers' => 'Customers',
    ];

    public const PERIODS = [
        'today' => 'Today', 'yesterday' => 'Yesterday', 'last7' => 'Last 7 days', 'last30' => 'Last 30 days',
        'thismonth' => 'This month', 'lastmonth' => 'Last month', 'thisyear' => 'This year', 'custom' => 'Custom',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ------------------------------------------------------------- periods
    // Returns ['from' => Y-m-d, 'to' => Y-m-d, 'label' => text, 'key' => preset, 'error' => text]
    public static function period(string $key, string $from, string $to): array
    {
        $today = new DateTimeImmutable('today');
        $error = '';

        switch ($key) {
            case 'today':     $f = $t = $today; break;
            case 'yesterday': $f = $t = $today->modify('-1 day'); break;
            case 'last7':     $f = $today->modify('-6 days'); $t = $today; break;
            case 'thismonth': $f = $today->modify('first day of this month'); $t = $today; break;
            case 'lastmonth': $f = $today->modify('first day of last month'); $t = $today->modify('last day of last month'); break;
            case 'thisyear':  $f = new DateTimeImmutable($today->format('Y') . '-01-01'); $t = $today; break;
            case 'custom':
                $a = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
                $b = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
                if (!$a || !$b || $a->format('Y-m-d') !== $from || $b->format('Y-m-d') !== $to) {
                    $error = 'Choose a start and an end date.';
                    $f = $today->modify('-29 days'); $t = $today; $key = 'last30';
                } elseif ($a > $b) {
                    $error = 'The start date must not be after the end date.';
                    $f = $today->modify('-29 days'); $t = $today; $key = 'last30';
                } elseif ($a->diff($b)->days > 366) {
                    $error = 'Choose a period of one year or less.';
                    $f = $today->modify('-29 days'); $t = $today; $key = 'last30';
                } else {
                    $f = $a; $t = $b;
                }
                break;
            default:          $key = 'last30'; $f = $today->modify('-29 days'); $t = $today;
        }

        $label = $f->format('Y-m-d') === $t->format('Y-m-d')
            ? $f->format('j M Y')
            : $f->format('j M Y') . ' to ' . $t->format('j M Y');
        return ['from' => $f->format('Y-m-d'), 'to' => $t->format('Y-m-d'), 'label' => $label, 'key' => $key, 'error' => $error];
    }

    public function build(string $type, string $from, string $to): array
    {
        switch ($type) {
            case 'bookings':  return $this->bookingsReport($from, $to);
            case 'earnings':  return $this->earningsReport($from, $to);
            case 'services':  return $this->servicesReport($from, $to);
            case 'cleaners':  return $this->cleanersReport($from, $to);
            case 'customers': return $this->customersReport($from, $to);
            default:          return $this->overviewReport($from, $to);
        }
    }

    // ---------------------------------------------------------- formatting
    public static function fmt($v, string $type): string
    {
        if ($v === null || $v === '') { return '–'; }
        switch ($type) {
            case 'money': return 'LKR ' . number_format((float)$v);
            case 'int':   return number_format((float)$v);
            case 'pct':   return number_format((float)$v, 1) . '%';
            case 'dec':   return number_format((float)$v, 1);
            case 'date':  return date('j M Y', strtotime((string)$v));
            case 'dt':    return date('j M Y, H:i', strtotime((string)$v));
            default:      return (string)$v;
        }
    }

    // A value for CSV: numbers stay numbers so a spreadsheet can add them up
    public static function raw($v, string $type): string
    {
        if ($v === null || $v === '') { return ''; }
        switch ($type) {
            case 'money': return number_format((float)$v, 2, '.', '');
            case 'int':   return (string)(int)$v;
            case 'pct': case 'dec': return number_format((float)$v, 1, '.', '');
            default:
                $s = (string)$v;
                // stop a spreadsheet from running text that starts like a formula
                return preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;
        }
    }

    // ------------------------------------------------------------- helpers
    private function one(string $sql, array $p = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchColumn();
    }

    private function rows(string $sql, array $p = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    private static function pct($part, $whole): float
    {
        return (float)$whole > 0 ? (float)$part / (float)$whole * 100 : 0.0;
    }

    private static function change($now, $before): string
    {
        $now = (float)$now; $before = (float)$before;
        if ($before == 0.0) { return $now > 0 ? 'new in this period' : ''; }
        $c = ($now - $before) / $before * 100;
        return sprintf('%s%.0f%% vs previous period', $c >= 0 ? '+' : '', $c);
    }

    // The period of the same length right before this one
    private static function previous(string $from, string $to): array
    {
        $f = new DateTimeImmutable($from);
        $t = new DateTimeImmutable($to);
        $days = $f->diff($t)->days + 1;
        $pt = $f->modify('-1 day');
        return [$pt->modify('-' . ($days - 1) . ' days')->format('Y-m-d'), $pt->format('Y-m-d')];
    }

    // Day buckets for a short period, month buckets for a long one: [key => label]
    private static function buckets(string $from, string $to): array
    {
        $f = new DateTimeImmutable($from);
        $t = new DateTimeImmutable($to);
        $byMonth = $f->diff($t)->days > 92;
        $out = [];
        for ($d = $byMonth ? $f->modify('first day of this month') : $f; $d <= $t; $d = $byMonth ? $d->modify('+1 month') : $d->modify('+1 day')) {
            $out[$d->format($byMonth ? 'Y-m' : 'Y-m-d')] = $d->format($byMonth ? 'M Y' : 'D j M');
        }
        return ['by' => $byMonth ? 'month' : 'day', 'items' => $out];
    }

    private static function bucketSql(string $col, string $by): string
    {
        return $by === 'month' ? "DATE_FORMAT($col, '%Y-%m')" : "DATE($col)";
    }

    private function revenue(string $f, string $t): array
    {
        $r = $this->rows(
            "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS n,
                    COALESCE(SUM(CASE WHEN method='online' THEN amount END),0) AS online,
                    COALESCE(SUM(CASE WHEN method='cash' THEN amount END),0) AS cash
             FROM payments WHERE payment_status='paid' AND payment_date >= :f AND payment_date < DATE_ADD(:t, INTERVAL 1 DAY)",
            ['f' => $f, 't' => $t]
        );
        return $r[0];
    }

    private function bookingCounts(string $f, string $t): array
    {
        $r = $this->rows(
            "SELECT COUNT(*) AS total,
                    SUM(b.status IN ('completed','finished')) AS done,
                    SUM(b.status = 'cancelled' AND (q.status IS NULL OR q.status <> 'rejected')) AS cancelled,
                    SUM(b.status = 'cancelled' AND q.status = 'rejected') AS rejected,
                    SUM(b.status IN ('requested','quoted','accepted','assigned','in_progress')) AS open
             FROM bookings b LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.appointment_date BETWEEN :f AND :t",
            ['f' => $f, 't' => $t]
        );
        return array_map('intval', $r[0]);
    }

    private function outstanding(): array
    {
        $r = $this->rows(
            "SELECT COUNT(*) AS n, COALESCE(SUM(q.quoted_price),0) AS total
             FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.status IN ('assigned','in_progress','completed')
               AND NOT EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status='paid')"
        );
        return $r[0];
    }

    private static function weekdayName(int $mysqlDow): string
    {
        return ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$mysqlDow];
    }

    // ============================================================= overview
    private function overviewReport(string $f, string $t): array
    {
        [$pf, $pt] = self::previous($f, $t);
        $b  = $this->bookingCounts($f, $t);   $pb = $this->bookingCounts($pf, $pt);
        $rv = $this->revenue($f, $t);         $pr = $this->revenue($pf, $pt);
        $out = $this->outstanding();
        $new  = (int)$this->one("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= :f AND created_at < DATE_ADD(:t, INTERVAL 1 DAY)", ['f' => $f, 't' => $t]);
        $pnew = (int)$this->one("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= :f AND created_at < DATE_ADD(:t, INTERVAL 1 DAY)", ['f' => $pf, 't' => $pt]);
        $rating = $this->one("SELECT AVG(rating) FROM reviews WHERE review_date >= :f AND review_date < DATE_ADD(:t, INTERVAL 1 DAY)", ['f' => $f, 't' => $t]);
        $lost = $b['cancelled'] + $b['rejected'];
        $lostRate = self::pct($lost, $b['total']);

        $kpis = [
            ['Money collected', $rv['total'], 'money', self::change($rv['total'], $pr['total'])],
            ['Bookings', $b['total'], 'int', self::change($b['total'], $pb['total'])],
            ['Completed', $b['done'], 'int', self::change($b['done'], $pb['done'])],
            ['Cancelled or rejected', $lostRate, 'pct', $lost . ' booking' . ($lost === 1 ? '' : 's')],
            ['Average payment', $rv['n'] ? $rv['total'] / $rv['n'] : null, 'money', ''],
            ['Unpaid right now', $out['total'], 'money', $out['n'] . ' booking' . ((int)$out['n'] === 1 ? '' : 's') . ' (all dates)'],
            ['New customers', $new, 'int', self::change($new, $pnew)],
            ['Average rating', $rating !== null && $rating !== false ? $rating : null, 'dec', 'out of 5'],
        ];

        // Trend
        $bk = self::buckets($f, $t);
        $bookingsBy = array_column($this->rows(
            "SELECT " . self::bucketSql('appointment_date', $bk['by']) . " AS k, COUNT(*) AS n FROM bookings
             WHERE appointment_date BETWEEN :f AND :t GROUP BY k", ['f' => $f, 't' => $t]), 'n', 'k');
        $moneyBy = array_column($this->rows(
            "SELECT " . self::bucketSql('payment_date', $bk['by']) . " AS k, SUM(amount) AS s FROM payments
             WHERE payment_status='paid' AND payment_date >= :f AND payment_date < DATE_ADD(:t, INTERVAL 1 DAY) GROUP BY k", ['f' => $f, 't' => $t]), 's', 'k');
        $trend = [];
        $bestDay = null;
        foreach ($bk['items'] as $k => $label) {
            $m = (float)($moneyBy[$k] ?? 0);
            $trend[] = [$label, (int)($bookingsBy[$k] ?? 0), $m];
            if ($m > 0 && ($bestDay === null || $m > $bestDay[1])) { $bestDay = [$label, $m]; }
        }

        // Status
        $statusRows = [];
        foreach ($this->rows(
            "SELECT CASE WHEN b.status='cancelled' AND q.status='rejected' THEN 'rejected' ELSE b.status END AS s, COUNT(*) AS n
             FROM bookings b LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.appointment_date BETWEEN :f AND :t GROUP BY s ORDER BY n DESC", ['f' => $f, 't' => $t]) as $r) {
            $statusRows[] = [booking_state($r['s'] === 'rejected' ? 'cancelled' : $r['s'], $r['s'] === 'rejected' ? 'rejected' : null), (int)$r['n'], self::pct($r['n'], $b['total'])];
        }

        // Ratings
        $dist = array_fill(1, 5, 0);
        foreach ($this->rows("SELECT rating, COUNT(*) AS n FROM reviews WHERE review_date >= :f AND review_date < DATE_ADD(:t, INTERVAL 1 DAY) GROUP BY rating", ['f' => $f, 't' => $t]) as $r) {
            $dist[(int)$r['rating']] = (int)$r['n'];
        }
        $nRev = array_sum($dist);
        $ratingRows = [];
        for ($i = 5; $i >= 1; $i--) { $ratingRows[] = [$i . ' star' . ($i === 1 ? '' : 's'), $dist[$i], self::pct($dist[$i], $nRev)]; }

        // Insights
        $ins = [];
        if ($rv['total'] > 0 && $pr['total'] > 0) {
            $c = ($rv['total'] - $pr['total']) / $pr['total'] * 100;
            $ins[] = sprintf('Money collected is %s %.0f%% compared with the previous period.', $c >= 0 ? 'up' : 'down', abs($c));
        }
        if ($bestDay) { $ins[] = 'Best earning ' . ($bk['by'] === 'month' ? 'month' : 'day') . ': ' . $bestDay[0] . ' (' . self::fmt($bestDay[1], 'money') . ').'; }
        if ($b['total'] >= 5 && $lostRate >= 20) { $ins[] = sprintf('%.0f%% of bookings were cancelled or rejected. Check the Bookings report to see why.', $lostRate); }
        if ((float)$out['total'] > 0) { $ins[] = self::fmt($out['total'], 'money') . ' is still unpaid on ' . $out['n'] . ' confirmed booking(s). Follow these up.'; }
        if ($nRev > 0 && $rating !== null && (float)$rating < 4) { $ins[] = sprintf('The average rating is %.1f. Read the low reviews and talk to the cleaners involved.', $rating); }
        if (!$ins) { $ins[] = 'Not enough activity in this period to point out anything yet.'; }

        return [
            'title' => 'Business summary',
            'basis' => 'Bookings are counted by appointment date, money by payment date.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => ($bk['by'] === 'month' ? 'Month by month' : 'Day by day'), 'cols' => [['Period', 'text'], ['Bookings', 'int'], ['Money collected', 'money']], 'rows' => $trend, 'bar' => 2],
                ['title' => 'Bookings by status', 'cols' => [['Status', 'text'], ['Bookings', 'int'], ['Share', 'pct']], 'rows' => $statusRows, 'bar' => 1],
                ['title' => 'Customer ratings', 'cols' => [['Rating', 'text'], ['Reviews', 'int'], ['Share', 'pct']], 'rows' => $ratingRows, 'bar' => 1],
            ],
        ];
    }

    // ============================================================ bookings
    private function bookingsReport(string $f, string $t): array
    {
        $b = $this->bookingCounts($f, $t);
        $lost = $b['cancelled'] + $b['rejected'];
        $lead = $this->one("SELECT AVG(DATEDIFF(appointment_date, DATE(created_at))) FROM bookings WHERE appointment_date BETWEEN :f AND :t", ['f' => $f, 't' => $t]);
        $site = (int)$this->one("SELECT COUNT(*) FROM bookings WHERE appointment_date BETWEEN :f AND :t AND location_type='customer_site'", ['f' => $f, 't' => $t]);

        $kpis = [
            ['Bookings', $b['total'], 'int', ''],
            ['Completed', $b['done'], 'int', self::fmt(self::pct($b['done'], $b['total']), 'pct') . ' of all'],
            ['Still open', $b['open'], 'int', 'not finished yet'],
            ['Cancelled', $b['cancelled'], 'int', 'by customer or manager'],
            ['Rejected', $b['rejected'], 'int', 'by the manager'],
            ['Cancelled or rejected', self::pct($lost, $b['total']), 'pct', ''],
            ['Booked ahead by', $lead !== null && $lead !== false ? $lead : null, 'dec', 'days on average'],
            ['At customer location', self::pct($site, $b['total']), 'pct', $site . ' booking' . ($site === 1 ? '' : 's')],
        ];

        $statusRows = [];
        foreach ($this->rows(
            "SELECT CASE WHEN b.status='cancelled' AND q.status='rejected' THEN 'rejected' ELSE b.status END AS s, COUNT(*) AS n
             FROM bookings b LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.appointment_date BETWEEN :f AND :t GROUP BY s ORDER BY n DESC", ['f' => $f, 't' => $t]) as $r) {
            $statusRows[] = [booking_state($r['s'] === 'rejected' ? 'cancelled' : $r['s'], $r['s'] === 'rejected' ? 'rejected' : null), (int)$r['n'], self::pct($r['n'], $b['total'])];
        }

        $locRows = [];
        foreach ($this->rows(
            "SELECT b.location_type, COUNT(*) AS n,
                    COALESCE(SUM((SELECT SUM(pm.amount) FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status='paid')),0) AS earned
             FROM bookings b WHERE b.appointment_date BETWEEN :f AND :t GROUP BY b.location_type", ['f' => $f, 't' => $t]) as $r) {
            $locRows[] = [location_label($r['location_type']), (int)$r['n'], self::pct($r['n'], $b['total']), (float)$r['earned']];
        }

        $dow = array_fill(1, 7, 0);
        foreach ($this->rows("SELECT DAYOFWEEK(appointment_date) AS d, COUNT(*) AS n FROM bookings WHERE appointment_date BETWEEN :f AND :t GROUP BY d", ['f' => $f, 't' => $t]) as $r) {
            $dow[(int)$r['d']] = (int)$r['n'];
        }
        $dowRows = [];
        foreach ([2, 3, 4, 5, 6, 7, 1] as $d) { $dowRows[] = [self::weekdayName($d), $dow[$d], self::pct($dow[$d], $b['total'])]; }

        $slots = ['Before 10:00' => 0, '10:00 to 12:59' => 0, '13:00 to 15:59' => 0, '16:00 and later' => 0];
        foreach ($this->rows("SELECT HOUR(appointment_time) AS h, COUNT(*) AS n FROM bookings WHERE appointment_date BETWEEN :f AND :t GROUP BY h", ['f' => $f, 't' => $t]) as $r) {
            $h = (int)$r['h'];
            $k = $h < 10 ? 'Before 10:00' : ($h < 13 ? '10:00 to 12:59' : ($h < 16 ? '13:00 to 15:59' : '16:00 and later'));
            $slots[$k] += (int)$r['n'];
        }
        $slotRows = [];
        foreach ($slots as $k => $n) { $slotRows[] = [$k, $n, self::pct($n, $b['total'])]; }

        $vehRows = [];
        foreach ($this->rows(
            "SELECT v.vehicle_type, COUNT(*) AS n,
                    COALESCE(SUM((SELECT SUM(pm.amount) FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status='paid')),0) AS earned
             FROM bookings b JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             WHERE b.appointment_date BETWEEN :f AND :t GROUP BY v.vehicle_type ORDER BY n DESC", ['f' => $f, 't' => $t]) as $r) {
            $vehRows[] = [ucfirst($r['vehicle_type']), (int)$r['n'], (float)$r['earned']];
        }

        $list = [];
        foreach ($this->rows(
            "SELECT b.booking_id, b.appointment_date, b.appointment_time, b.status, u.full_name, v.make_model, v.plate_no, p.package_name,
                    q.quoted_price, q.status AS q_status
             FROM bookings b
             JOIN users u ON u.user_id = b.user_id
             JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             JOIN packages p ON p.package_id = b.package_id
             LEFT JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.appointment_date BETWEEN :f AND :t
             ORDER BY b.appointment_date, b.appointment_time LIMIT 500", ['f' => $f, 't' => $t]) as $r) {
            $list[] = ['#' . $r['booking_id'], $r['appointment_date'], substr($r['appointment_time'], 0, 5), $r['full_name'],
                       $r['make_model'] . ' ' . $r['plate_no'], $r['package_name'], booking_state($r['status'], $r['q_status']),
                       $r['q_status'] === 'rejected' ? null : $r['quoted_price']];
        }

        // Insights
        $ins = [];
        if ($b['total'] > 0) {
            $max = max($dow); $min = min($dow);
            $maxD = array_search($max, $dow); $minD = array_search($min, $dow);
            if ($max > 0) { $ins[] = 'Busiest day of the week: ' . self::weekdayName((int)$maxD) . ' (' . $max . ' bookings). Make sure enough cleaners are on duty.'; }
            if ($b['total'] >= 7 && $min < $max) { $ins[] = 'Quietest day: ' . self::weekdayName((int)$minD) . ' (' . $min . '). A discount or promotion could fill it.'; }
            $topSlot = array_search(max($slots), $slots);
            if (max($slots) > 0) { $ins[] = 'Most popular time of day: ' . $topSlot . '.'; }
            if (self::pct($site, $b['total']) >= 50) { $ins[] = 'More than half of the bookings are at the customer’s location, so travel time matters when you assign cleaners.'; }
            if ($b['total'] >= 5 && self::pct($b['cancelled'], $b['total']) >= 15) { $ins[] = sprintf('%.0f%% of bookings were cancelled. Contact those customers to find out why.', self::pct($b['cancelled'], $b['total'])); }
        } else {
            $ins[] = 'There are no bookings in this period.';
        }

        return [
            'title' => 'Bookings report',
            'basis' => 'Bookings with an appointment date in this period. The list shows up to 500 bookings.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => 'Bookings by status', 'cols' => [['Status', 'text'], ['Bookings', 'int'], ['Share', 'pct']], 'rows' => $statusRows, 'bar' => 1],
                ['title' => 'Where the work is done', 'cols' => [['Location', 'text'], ['Bookings', 'int'], ['Share', 'pct'], ['Money collected', 'money']], 'rows' => $locRows, 'bar' => 1],
                ['title' => 'Bookings by day of the week', 'cols' => [['Day', 'text'], ['Bookings', 'int'], ['Share', 'pct']], 'rows' => $dowRows, 'bar' => 1],
                ['title' => 'Bookings by time of day', 'cols' => [['Time', 'text'], ['Bookings', 'int'], ['Share', 'pct']], 'rows' => $slotRows, 'bar' => 1],
                ['title' => 'Bookings by vehicle type', 'cols' => [['Vehicle type', 'text'], ['Bookings', 'int'], ['Money collected', 'money']], 'rows' => $vehRows, 'bar' => 1],
                ['title' => 'All bookings in this period', 'cols' => [['Booking', 'text'], ['Date', 'date'], ['Time', 'text'], ['Customer', 'text'], ['Vehicle', 'text'], ['Package', 'text'], ['Status', 'text'], ['Quoted price', 'money']], 'rows' => $list, 'bar' => null],
            ],
        ];
    }

    // ============================================================ earnings
    private function earningsReport(string $f, string $t): array
    {
        [$pf, $pt] = self::previous($f, $t);
        $rv = $this->revenue($f, $t); $pr = $this->revenue($pf, $pt);
        $out = $this->outstanding();
        $lost = $this->rows(
            "SELECT COUNT(*) AS n, COALESCE(SUM(q.quoted_price),0) AS total
             FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id
             WHERE b.status='cancelled' AND q.status='sent' AND b.appointment_date BETWEEN :f AND :t", ['f' => $f, 't' => $t])[0];

        $kpis = [
            ['Money collected', $rv['total'], 'money', self::change($rv['total'], $pr['total'])],
            ['Online payments', $rv['online'], 'money', self::fmt(self::pct($rv['online'], $rv['total']), 'pct') . ' of the total'],
            ['Cash payments', $rv['cash'], 'money', self::fmt(self::pct($rv['cash'], $rv['total']), 'pct') . ' of the total'],
            ['Payments', $rv['n'], 'int', self::change($rv['n'], $pr['n'])],
            ['Average payment', $rv['n'] ? $rv['total'] / $rv['n'] : null, 'money', ''],
            ['Unpaid right now', $out['total'], 'money', $out['n'] . ' booking(s), all dates'],
            ['Lost to cancellations', $lost['total'], 'money', $lost['n'] . ' quoted booking(s) cancelled'],
        ];

        $methodRows = [];
        foreach ($this->rows(
            "SELECT method, COUNT(*) AS n, SUM(amount) AS s FROM payments
             WHERE payment_status='paid' AND payment_date >= :f AND payment_date < DATE_ADD(:t, INTERVAL 1 DAY) GROUP BY method ORDER BY s DESC", ['f' => $f, 't' => $t]) as $r) {
            $methodRows[] = [method_label($r['method']), (int)$r['n'], (float)$r['s'], self::pct($r['s'], $rv['total'])];
        }

        $bk = self::buckets($f, $t);
        $by = [];
        foreach ($this->rows(
            "SELECT " . self::bucketSql('payment_date', $bk['by']) . " AS k, COUNT(*) AS n, SUM(amount) AS s FROM payments
             WHERE payment_status='paid' AND payment_date >= :f AND payment_date < DATE_ADD(:t, INTERVAL 1 DAY) GROUP BY k", ['f' => $f, 't' => $t]) as $r) {
            $by[$r['k']] = $r;
        }
        $timeRows = []; $best = null;
        foreach ($bk['items'] as $k => $label) {
            $s = (float)($by[$k]['s'] ?? 0);
            $timeRows[] = [$label, (int)($by[$k]['n'] ?? 0), $s];
            if ($s > 0 && ($best === null || $s > $best[1])) { $best = [$label, $s]; }
        }

        $payList = [];
        foreach ($this->rows(
            "SELECT pay.payment_date, b.booking_id, u.full_name, p.package_name, pay.method, pay.amount
             FROM payments pay JOIN bookings b ON b.booking_id = pay.booking_id
             JOIN users u ON u.user_id = b.user_id JOIN packages p ON p.package_id = b.package_id
             WHERE pay.payment_status='paid' AND pay.payment_date >= :f AND pay.payment_date < DATE_ADD(:t, INTERVAL 1 DAY)
             ORDER BY pay.payment_date LIMIT 500", ['f' => $f, 't' => $t]) as $r) {
            $payList[] = [$r['payment_date'], '#' . $r['booking_id'], $r['full_name'], $r['package_name'], method_label($r['method']), (float)$r['amount']];
        }

        $unpaid = [];
        foreach ($this->rows(
            "SELECT b.booking_id, u.full_name, u.phone, b.appointment_date, b.status, q.quoted_price
             FROM bookings b JOIN quotations q ON q.booking_id = b.booking_id JOIN users u ON u.user_id = b.user_id
             WHERE b.status IN ('assigned','in_progress','completed')
               AND NOT EXISTS (SELECT 1 FROM payments pm WHERE pm.booking_id = b.booking_id AND pm.payment_status='paid')
             ORDER BY b.appointment_date LIMIT 200") as $r) {
            $unpaid[] = ['#' . $r['booking_id'], $r['full_name'], $r['phone'], $r['appointment_date'], status_label($r['status']), (float)$r['quoted_price']];
        }

        $ins = [];
        if ($rv['total'] > 0) {
            $ins[] = sprintf('%.0f%% of the money came in online and %.0f%% in cash.', self::pct($rv['online'], $rv['total']), self::pct($rv['cash'], $rv['total']));
            if (self::pct($rv['cash'], $rv['total']) >= 50) { $ins[] = 'Most payments are cash. Cleaners are holding that money, so collect it from them regularly (see the Cleaners report).'; }
            if ($best) { $ins[] = 'Best earning ' . ($bk['by'] === 'month' ? 'month' : 'day') . ': ' . $best[0] . ' (' . self::fmt($best[1], 'money') . ').'; }
        } else {
            $ins[] = 'No payments were received in this period.';
        }
        if ((float)$out['total'] > 0) { $ins[] = self::fmt($out['total'], 'money') . ' is waiting to be paid. The list below has the customers’ phone numbers.'; }
        if ((float)$lost['total'] > 0) { $ins[] = 'Cancelled bookings cost about ' . self::fmt($lost['total'], 'money') . ' in quoted work.'; }

        return [
            'title' => 'Earnings report',
            'basis' => 'Payments counted on the day they were made. “Unpaid right now” covers every date.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => 'Online and cash', 'cols' => [['Method', 'text'], ['Payments', 'int'], ['Amount', 'money'], ['Share', 'pct']], 'rows' => $methodRows, 'bar' => 2],
                ['title' => ($bk['by'] === 'month' ? 'Earnings month by month' : 'Earnings day by day'), 'cols' => [['Period', 'text'], ['Payments', 'int'], ['Amount', 'money']], 'rows' => $timeRows, 'bar' => 2],
                ['title' => 'Unpaid bookings (follow up)', 'cols' => [['Booking', 'text'], ['Customer', 'text'], ['Phone', 'text'], ['Appointment', 'date'], ['Status', 'text'], ['Amount due', 'money']], 'rows' => $unpaid, 'bar' => null],
                ['title' => 'All payments in this period', 'cols' => [['Paid on', 'dt'], ['Booking', 'text'], ['Customer', 'text'], ['Package', 'text'], ['Method', 'text'], ['Amount', 'money']], 'rows' => $payList, 'bar' => null],
            ],
        ];
    }

    // ============================================================ services
    private function servicesReport(string $f, string $t): array
    {
        $pk = $this->rows(
            "SELECT p.package_id, p.package_name, p.min_price, p.max_price,
                    (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.package_id AND b.appointment_date BETWEEN :f1 AND :t1) AS bookings,
                    (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.package_id AND b.appointment_date BETWEEN :f2 AND :t2 AND b.status IN ('completed','finished')) AS done,
                    (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.package_id AND b.appointment_date BETWEEN :f3 AND :t3 AND b.status = 'cancelled') AS lost,
                    (SELECT COALESCE(SUM(pm.amount),0) FROM payments pm JOIN bookings b ON b.booking_id = pm.booking_id
                      WHERE b.package_id = p.package_id AND pm.payment_status='paid' AND pm.payment_date >= :f4 AND pm.payment_date < DATE_ADD(:t4, INTERVAL 1 DAY)) AS earned,
                    (SELECT COUNT(*) FROM payments pm JOIN bookings b ON b.booking_id = pm.booking_id
                      WHERE b.package_id = p.package_id AND pm.payment_status='paid' AND pm.payment_date >= :f5 AND pm.payment_date < DATE_ADD(:t5, INTERVAL 1 DAY)) AS paid_n,
                    (SELECT AVG(q.quoted_price) FROM quotations q JOIN bookings b ON b.booking_id = q.booking_id
                      WHERE b.package_id = p.package_id AND q.status <> 'rejected' AND b.appointment_date BETWEEN :f6 AND :t6) AS avg_quote,
                    (SELECT AVG(r.rating) FROM reviews r JOIN bookings b ON b.booking_id = r.booking_id
                      WHERE b.package_id = p.package_id AND r.review_date >= :f7 AND r.review_date < DATE_ADD(:t7, INTERVAL 1 DAY)) AS rating
             FROM packages p ORDER BY p.package_id",
            ['f1' => $f, 't1' => $t, 'f2' => $f, 't2' => $t, 'f3' => $f, 't3' => $t, 'f4' => $f, 't4' => $t, 'f5' => $f, 't5' => $t, 'f6' => $f, 't6' => $t, 'f7' => $f, 't7' => $t]
        );

        $totalEarned = array_sum(array_column($pk, 'earned'));
        $totalBookings = array_sum(array_column($pk, 'bookings'));
        usort($pk, function ($a, $b) { return (float)$b['earned'] <=> (float)$a['earned'] ?: (int)$b['bookings'] <=> (int)$a['bookings']; });

        $rows = [];
        foreach ($pk as $p) {
            $rows[] = [$p['package_name'], (int)$p['bookings'], (int)$p['done'], self::pct($p['lost'], $p['bookings']),
                       (float)$p['earned'], self::pct($p['earned'], $totalEarned),
                       $p['paid_n'] ? $p['earned'] / $p['paid_n'] : null, $p['avg_quote'] !== null ? (float)$p['avg_quote'] : null,
                       number_format((float)$p['min_price']) . ' - ' . number_format((float)$p['max_price']),
                       $p['rating'] !== null ? (float)$p['rating'] : null];
        }

        $cond = [];
        foreach ($this->rows(
            "SELECT p.package_name,
                    SUM(q.condition_level='light') AS l, SUM(q.condition_level='moderate') AS m, SUM(q.condition_level='heavy') AS h
             FROM quotations q JOIN bookings b ON b.booking_id = q.booking_id JOIN packages p ON p.package_id = b.package_id
             WHERE q.status <> 'rejected' AND b.appointment_date BETWEEN :f AND :t GROUP BY p.package_id, p.package_name", ['f' => $f, 't' => $t]) as $r) {
            $cond[] = [$r['package_name'], (int)$r['l'], (int)$r['m'], (int)$r['h']];
        }

        $veh = [];
        foreach ($this->rows(
            "SELECT v.vehicle_type, COUNT(DISTINCT b.booking_id) AS n, COALESCE(SUM(pm.amount),0) AS s
             FROM bookings b JOIN vehicles v ON v.vehicle_id = b.vehicle_id
             LEFT JOIN payments pm ON pm.booking_id = b.booking_id AND pm.payment_status='paid'
             WHERE b.appointment_date BETWEEN :f AND :t GROUP BY v.vehicle_type ORDER BY s DESC", ['f' => $f, 't' => $t]) as $r) {
            $veh[] = [ucfirst($r['vehicle_type']), (int)$r['n'], (float)$r['s']];
        }

        $top = $pk[0] ?? null;
        $kpis = [
            ['Earned from all services', $totalEarned, 'money', ''],
            ['Bookings', $totalBookings, 'int', ''],
            ['Top earner', $top && (float)$top['earned'] > 0 ? $top['package_name'] : null, 'text', $top && (float)$top['earned'] > 0 ? self::fmt(self::pct($top['earned'], $totalEarned), 'pct') . ' of earnings' : ''],
        ];

        $ins = [];
        if ($top && (float)$top['earned'] > 0) { $ins[] = $top['package_name'] . ' earned the most (' . self::fmt($top['earned'], 'money') . ', ' . round(self::pct($top['earned'], $totalEarned)) . '% of the total).'; }
        foreach ($pk as $p) {
            if ((int)$p['bookings'] >= 5 && self::pct($p['lost'], $p['bookings']) >= 25) {
                $ins[] = sprintf('%s has a high cancellation rate (%.0f%%). Check the price range or the description.', $p['package_name'], self::pct($p['lost'], $p['bookings']));
            }
            if ($p['avg_quote'] !== null && (float)$p['max_price'] > 0 && (float)$p['avg_quote'] >= 0.9 * (float)$p['max_price']) {
                $ins[] = $p['package_name'] . ': quotes average close to the top of the price range. Consider raising the range.';
            }
            if ((int)$p['bookings'] === 0 && $totalBookings > 0) {
                $ins[] = $p['package_name'] . ' had no bookings in this period. Promote it, or make it inactive.';
            }
            if ($p['rating'] !== null && (float)$p['rating'] < 3.5) {
                $ins[] = sprintf('%s is rated %.1f. Look at its reviews.', $p['package_name'], $p['rating']);
            }
        }
        if (!$ins) { $ins[] = 'No bookings or payments in this period yet.'; }

        return [
            'title' => 'Services report',
            'basis' => 'Bookings by appointment date, earnings by payment date. Includes inactive packages.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => 'How much each service earned', 'cols' => [['Package', 'text'], ['Bookings', 'int'], ['Completed', 'int'], ['Cancel rate', 'pct'], ['Earned', 'money'], ['Share of earnings', 'pct'], ['Average payment', 'money'], ['Average quote', 'money'], ['Price range (LKR)', 'text'], ['Rating', 'dec']], 'rows' => $rows, 'bar' => 4],
                ['title' => 'Vehicle condition quoted', 'cols' => [['Package', 'text'], ['Light', 'int'], ['Moderate', 'int'], ['Heavy', 'int']], 'rows' => $cond, 'bar' => null, 'note' => 'Many heavy jobs on a package may mean its price range is too low.'],
                ['title' => 'Earnings by vehicle type', 'cols' => [['Vehicle type', 'text'], ['Bookings', 'int'], ['Earned', 'money']], 'rows' => $veh, 'bar' => 2],
            ],
        ];
    }

    // ============================================================ cleaners
    private function cleanersReport(string $f, string $t): array
    {
        $rows = $this->rows(
            "SELECT u.user_id, u.full_name, u.status,
                    (SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f1 AND :t1) AS jobs,
                    (SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f2 AND :t2 AND b.status IN ('completed','finished')) AS done,
                    (SELECT COUNT(*) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f3 AND :t3 AND b.status = 'cancelled') AS cancelled,
                    (SELECT COALESCE(SUM(pm.amount),0) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id JOIN payments pm ON pm.booking_id = b.booking_id AND pm.payment_status='paid'
                      WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f4 AND :t4) AS earned,
                    (SELECT COALESCE(SUM(pm.amount),0) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id JOIN payments pm ON pm.booking_id = b.booking_id AND pm.payment_status='paid' AND pm.method='cash'
                      WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f5 AND :t5) AS cash,
                    (SELECT AVG(TIMESTAMPDIFF(MINUTE, j.arrived_at, j.completed_at)) FROM jobs j JOIN bookings b ON b.booking_id = j.booking_id
                      WHERE j.cleaner_id = u.user_id AND b.appointment_date BETWEEN :f6 AND :t6 AND j.arrived_at IS NOT NULL AND j.completed_at IS NOT NULL) AS minutes,
                    (SELECT AVG(r.rating) FROM jobs j JOIN reviews r ON r.booking_id = j.booking_id
                      WHERE j.cleaner_id = u.user_id AND r.review_date >= :f7 AND r.review_date < DATE_ADD(:t7, INTERVAL 1 DAY)) AS rating
             FROM users u WHERE u.role = 'cleaner' ORDER BY jobs DESC, u.full_name",
            ['f1' => $f, 't1' => $t, 'f2' => $f, 't2' => $t, 'f3' => $f, 't3' => $t, 'f4' => $f, 't4' => $t, 'f5' => $f, 't5' => $t, 'f6' => $f, 't6' => $t, 'f7' => $f, 't7' => $t]
        );

        $table = [];
        $totJobs = 0; $totCash = 0.0; $working = 0; $ratings = [];
        foreach ($rows as $r) {
            $totJobs += (int)$r['jobs']; $totCash += (float)$r['cash'];
            if ((int)$r['jobs'] > 0) { $working++; }
            if ($r['rating'] !== null) { $ratings[] = (float)$r['rating']; }
            $table[] = [$r['full_name'] . ($r['status'] === 'blocked' ? ' (blocked)' : ''), (int)$r['jobs'], (int)$r['done'], (int)$r['cancelled'],
                        (float)$r['earned'], (float)$r['cash'], $r['minutes'] !== null ? (float)$r['minutes'] : null, $r['rating'] !== null ? (float)$r['rating'] : null];
        }

        $kpis = [
            ['Cleaners', count($rows), 'int', $working . ' had jobs in this period'],
            ['Jobs', $totJobs, 'int', $working ? number_format($totJobs / $working, 1) . ' per working cleaner' : ''],
            ['Cash collected by cleaners', $totCash, 'money', 'to be handed over'],
            ['Average rating', $ratings ? array_sum($ratings) / count($ratings) : null, 'dec', 'across cleaners'],
        ];

        $ins = [];
        if ($totJobs > 0) {
            $busiest = $rows[0];
            $ins[] = $busiest['full_name'] . ' did the most jobs (' . (int)$busiest['jobs'] . ').';
            $rated = array_filter($rows, function ($r) { return $r['rating'] !== null; });
            if ($rated) {
                usort($rated, function ($a, $b) { return (float)$b['rating'] <=> (float)$a['rating']; });
                $ins[] = 'Best rated: ' . $rated[0]['full_name'] . ' (' . number_format((float)$rated[0]['rating'], 1) . ').';
                $low = end($rated);
                if (count($rated) > 1 && (float)$low['rating'] < 4) { $ins[] = $low['full_name'] . ' has the lowest rating (' . number_format((float)$low['rating'], 1) . '). Consider some coaching.'; }
            }
            if ($totCash > 0) { $ins[] = 'Cleaners collected ' . self::fmt($totCash, 'money') . ' in cash. Make sure it is handed over.'; }
            foreach ($rows as $r) {
                if ($r['status'] === 'active' && (int)$r['jobs'] === 0 && count($rows) > 1) { $ins[] = $r['full_name'] . ' had no jobs in this period. There may be room to give them more work.'; }
            }
        } else {
            $ins[] = 'No jobs in this period.';
        }

        return [
            'title' => 'Cleaners report',
            'basis' => 'Jobs by appointment date. Job time is measured from “Start job” to “Complete job”.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => 'Cleaner performance', 'cols' => [['Cleaner', 'text'], ['Jobs', 'int'], ['Completed', 'int'], ['Cancelled', 'int'], ['Money collected', 'money'], ['Of which cash', 'money'], ['Average job time (min)', 'dec'], ['Rating', 'dec']], 'rows' => $table, 'bar' => 1],
            ],
        ];
    }

    // =========================================================== customers
    private function customersReport(string $f, string $t): array
    {
        $totalCust = (int)$this->one("SELECT COUNT(*) FROM users WHERE role='customer'");
        $new = (int)$this->one("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= :f AND created_at < DATE_ADD(:t, INTERVAL 1 DAY)", ['f' => $f, 't' => $t]);
        $active = (int)$this->one("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE appointment_date BETWEEN :f AND :t", ['f' => $f, 't' => $t]);
        $returning = (int)$this->one(
            "SELECT COUNT(DISTINCT b.user_id) FROM bookings b WHERE b.appointment_date BETWEEN :f AND :t
               AND EXISTS (SELECT 1 FROM bookings o WHERE o.user_id = b.user_id AND o.appointment_date < :f2 AND o.status IN ('completed','finished'))",
            ['f' => $f, 't' => $t, 'f2' => $f]);
        $spent = (float)$this->one(
            "SELECT COALESCE(SUM(pm.amount),0) FROM payments pm WHERE pm.payment_status='paid' AND pm.payment_date >= :f AND pm.payment_date < DATE_ADD(:t, INTERVAL 1 DAY)",
            ['f' => $f, 't' => $t]);

        $kpis = [
            ['All customers', $totalCust, 'int', ''],
            ['New customers', $new, 'int', 'registered in this period'],
            ['Customers who booked', $active, 'int', ''],
            ['Returning customers', $returning, 'int', self::fmt(self::pct($returning, $active), 'pct') . ' of those who booked'],
            ['Average spend per customer', $active ? $spent / $active : null, 'money', 'money collected / customers who booked'],
        ];

        $top = [];
        foreach ($this->rows(
            "SELECT u.full_name, u.phone, COUNT(DISTINCT b.booking_id) AS n,
                    COUNT(DISTINCT CASE WHEN b.status IN ('completed','finished') THEN b.booking_id END) AS done,
                    COALESCE(SUM(pm.amount),0) AS s
             FROM bookings b JOIN users u ON u.user_id = b.user_id
             LEFT JOIN payments pm ON pm.booking_id = b.booking_id AND pm.payment_status='paid'
             WHERE b.appointment_date BETWEEN :f AND :t
             GROUP BY u.user_id, u.full_name, u.phone ORDER BY s DESC, n DESC LIMIT 15", ['f' => $f, 't' => $t]) as $r) {
            $top[] = [$r['full_name'], $r['phone'], (int)$r['n'], (int)$r['done'], (float)$r['s']];
        }

        $newList = [];
        foreach ($this->rows(
            "SELECT u.full_name, u.phone, u.created_at, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) AS n
             FROM users u WHERE u.role='customer' AND u.created_at >= :f AND u.created_at < DATE_ADD(:t, INTERVAL 1 DAY)
             ORDER BY u.created_at DESC LIMIT 200", ['f' => $f, 't' => $t]) as $r) {
            $newList[] = [$r['full_name'], $r['phone'], $r['created_at'], (int)$r['n']];
        }

        // Customers who used the service but have not been back for 60 days (to win back)
        $lapsed = [];
        foreach ($this->rows(
            "SELECT u.full_name, u.phone, MAX(b.appointment_date) AS last_visit, COUNT(*) AS n
             FROM users u JOIN bookings b ON b.user_id = u.user_id AND b.status IN ('completed','finished')
             WHERE u.role='customer' AND u.status='active'
             GROUP BY u.user_id, u.full_name, u.phone
             HAVING MAX(b.appointment_date) < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                AND NOT EXISTS (SELECT 1 FROM bookings o WHERE o.user_id = u.user_id AND o.appointment_date >= CURDATE() AND o.status NOT IN ('cancelled'))
             ORDER BY last_visit DESC LIMIT 20") as $r) {
            $lapsed[] = [$r['full_name'], $r['phone'], $r['last_visit'], (int)$r['n']];
        }

        $ins = [];
        if ($active > 0) {
            $ins[] = sprintf('%.0f%% of the customers who booked had used the service before.', self::pct($returning, $active));
            if (self::pct($returning, $active) < 30 && $active >= 5) { $ins[] = 'Few customers come back. Consider a loyalty offer or a reminder message after each service.'; }
        }
        if ($new > 0) { $ins[] = $new . ' new customer' . ($new === 1 ? '' : 's') . ' registered in this period.'; }
        if ($lapsed) { $ins[] = count($lapsed) . ' customer(s) have not been back for over 60 days. Call or message them (list below).'; }
        if (!$ins) { $ins[] = 'No customer activity in this period.'; }

        return [
            'title' => 'Customers report',
            'basis' => 'Customers by their bookings in this period. “Returning” means they had a completed booking before the period. Not back for 60 days is measured from today.',
            'kpis' => $kpis, 'insights' => $ins,
            'sections' => [
                ['title' => 'Top customers', 'cols' => [['Customer', 'text'], ['Phone', 'text'], ['Bookings', 'int'], ['Completed', 'int'], ['Paid', 'money']], 'rows' => $top, 'bar' => 4],
                ['title' => 'New customers', 'cols' => [['Customer', 'text'], ['Phone', 'text'], ['Joined', 'dt'], ['Bookings so far', 'int']], 'rows' => $newList, 'bar' => null],
                ['title' => 'Not back for 60 days (win back)', 'cols' => [['Customer', 'text'], ['Phone', 'text'], ['Last visit', 'date'], ['Completed bookings', 'int']], 'rows' => $lapsed, 'bar' => null],
            ],
        ];
    }
}
