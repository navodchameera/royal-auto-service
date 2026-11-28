<?php
defined('APP_PATH') or exit;

// The bill of a completed booking: view it, print it, or download it as a PDF.
// A customer sees their own bills; the manager can open any bill.
class BillController extends Controller
{
    public function show(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }
        $user = $_SESSION['user'];
        if (!in_array($user['role'], ['customer', 'manager'], true)) { $this->redirect('home'); }

        $id   = (int)($_GET['id'] ?? 0);
        $bill = (new Bill())->forBooking($id, $user['role'] === 'customer' ? (int)$user['id'] : 0);
        if (!$bill) {
            $_SESSION['flash'] = 'A bill is available once a booking is completed.';
            $this->redirect($user['role'] === 'manager' ? 'manage-bookings' : 'bookings&filter=completed');
        }

        $number = 'RAS-' . str_pad((string)$bill['booking_id'], 5, '0', STR_PAD_LEFT);

        if (isset($_GET['download'])) {
            $pdf = $this->pdf($bill, $number);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Royal-Auto-Bill-' . $number . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            header('X-Content-Type-Options: nosniff');
            echo $pdf;
            exit;
        }

        $this->view('customer/bill', [
            'user' => $user, 'b' => $bill, 'number' => $number,
            'backUrl' => $user['role'] === 'manager' ? 'manage-bookings&id=' . $id : 'bookings&filter=completed',
        ]);
    }

    private function pdf(array $b, string $number): string
    {
        $p = new SimplePdf();
        $L = 48; $R = 547;                         // left and right margins

        // Header band
        $p->rect(0, 0, 595.28, 96, '#0B1B2B');
        $p->text($L, 48, 'Royal Auto Service', 22, true, '#FFFFFF');
        $p->text($L, 68, 'Car wash and detailing', 10, false, '#6FD0E0');
        $p->text($R, 48, 'BILL', 22, true, '#6FD0E0', 'r');
        $p->text($R, 68, $number, 10, false, '#FFFFFF', 'r');

        $y = 130;
        $p->text($L, $y, 'Billed to', 9, false, '#475B6C');
        $p->text($L, $y + 16, $b['full_name'], 12, true);
        $p->text($L, $y + 31, $b['phone'] . '  |  ' . $b['email'], 9);
        $p->text($L, $y + 44, $b['cust_address'], 9);
        $p->text($R, $y, 'Booking date', 9, false, '#475B6C', 'r');
        $p->text($R, $y + 16, date('j M Y', strtotime($b['created_at'])), 12, true, '#0B1B2B', 'r');
        $p->text($R, $y + 31, 'Paid ' . ($b['payment_date'] ? date('j M Y', strtotime($b['payment_date'])) : ''), 9, false, '#0B1B2B', 'r');

        $y = 205;
        $section = function (string $title, array $rows) use ($p, &$y, $L, $R): void {
            $p->text($L, $y, strtoupper($title), 9, true, '#0A7C91');
            $p->line($L, $y + 6, $R);
            $y += 24;
            foreach ($rows as [$k, $v]) {
                $p->text($L, $y, $k, 10, false, '#475B6C');
                $p->text($R, $y, $v, 10, true, '#0B1B2B', 'r');
                $y += 19;
            }
            $y += 10;
        };

        $section('Service', [
            ['Package', $b['package_name']],
            ['Vehicle', $b['make_model'] . ' (' . $b['plate_no'] . ')'],
            ['Appointment', date('D j M Y', strtotime($b['appointment_date'])) . ', ' . substr($b['appointment_time'], 0, 5)],
            ['Place', location_label($b['location_type']) . ($b['location_type'] === 'customer_site' && $b['job_address'] ? ' - ' . $b['job_address'] : '')],
            ['Cleaner', $b['cleaner_name'] ?: '-'],
            ['Vehicle condition', $b['condition_level'] ? ucfirst($b['condition_level']) : '-'],
        ]);

        $section('Timeline', [
            ['Booking created', when_label($b['created_at'])],
            ['Quotation sent', when_label($b['quotation_date'])],
            ['Job started (cleaner arrived)', when_label($b['arrived_at'])],
            ['Job completed', when_label($b['completed_at'])],
            ['Payment received', when_label($b['payment_date'])],
        ]);

        // Charges
        $p->text($L, $y, 'CHARGES', 9, true, '#0A7C91');
        $p->line($L, $y + 6, $R);
        $y += 24;
        $p->text($L, $y, $b['package_name'] . ' package (quoted price)', 10, false, '#475B6C');
        $p->text($R, $y, money($b['quoted_price']), 10, true, '#0B1B2B', 'r');
        $y += 22;
        $p->rect($L, $y - 15, $R - $L, 30, '#F0F8FA');
        $p->text($L + 10, $y + 4, 'Total', 12, true);
        $p->text($R - 10, $y + 4, money($b['quoted_price']), 14, true, '#0A7C91', 'r');
        $y += 34;
        $p->text($L, $y, 'Paid by ' . strtolower(method_label((string)$b['pay_method'])) . ' on ' . when_label($b['payment_date']), 10, false, '#475B6C');
        $p->text($R, $y, 'Balance due: ' . money(0), 10, true, '#0B1B2B', 'r');
        $y += 36;

        if ($b['rating'] !== null) {
            $p->text($L, $y, 'Your rating: ' . (int)$b['rating'] . ' / 5', 10, true);
            $y += 16;
        }
        $p->line($L, 790, $R);
        $p->text(297.64 - $p->width('Thank you for choosing Royal Auto Service.', 9) / 2, 808, 'Thank you for choosing Royal Auto Service.', 9, false, '#475B6C');
        $p->text(297.64 - $p->width('Payment shown was recorded in the system.', 8) / 2, 822, 'Payment shown was recorded in the system.', 8, false, '#475B6C');

        return $p->output();
    }
}
