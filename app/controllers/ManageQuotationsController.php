<?php
defined('APP_PATH') or exit;

class ManageQuotationsController extends Controller
{
    private const FILTERS = ['requested' => 'To quote', 'sent' => 'Sent'];
    private const LEVELS  = ['light' => 'Light', 'moderate' => 'Moderate', 'heavy' => 'Heavy'];

    public function index(): void
    {
        $user = $this->requireRole('manager');
        $m    = new Manage();

        $filter = (string)($_GET['filter'] ?? 'requested');
        if (!isset(self::FILTERS[$filter])) { $filter = 'requested'; }

        $errors = [];
        $old    = ['condition_level' => '', 'quoted_price' => '', 'remarks' => '', 'cleaner_id' => ''];
        $id     = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;
        $posted = $_SERVER['REQUEST_METHOD'] === 'POST';

        if ($posted) {
            $id = (int)($_POST['booking_id'] ?? 0);
            foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validate($old);
                if (!$errors) {
                    $price  = number_format((float)str_replace(',', '', $old['quoted_price']), 2, '.', '');
                    $result = $m->saveQuotation($id, $price, $old['condition_level'], $old['remarks'], (int)$old['cleaner_id']);
                    if ($result === '') {
                        $_SESSION['flash'] = 'Quotation for booking #' . $id . ' was sent. The booking is confirmed and the cleaner is assigned.';
                        $this->redirect('manage-quotations&filter=requested');
                    }
                    $errors['form'] = $result;
                }
            }
        }

        $queue = $m->quoteQueue($filter);
        if (!$id && $queue) { $id = (int)$queue[0]['booking_id']; }

        $detail   = $id ? $m->booking($id) : null;
        $canQuote = $detail && in_array($detail['status'], ['requested', 'assigned'], true);

        // Cleaners who are free on the customer's date and time
        $free = [];
        $busy = [];
        if ($detail && $canQuote) {
            foreach ($m->cleanersFor($id, $detail['appointment_date'], $detail['appointment_time']) as $c) {
                if ((int)$c['conflicts'] === 0) { $free[] = $c; } else { $busy[] = $c; }
            }
        }

        // Re-opening a quotation that was already sent: show what was sent
        if ($detail && $canQuote && !$posted && $detail['quoted_price'] !== null) {
            $old = [
                'condition_level' => (string)$detail['condition_level'],
                'quoted_price'    => (string)(float)$detail['quoted_price'],
                'remarks'         => (string)$detail['remarks'],
                'cleaner_id'      => (string)$detail['cleaner_id'],
            ];
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/quotations', [
            'user' => $user, 'filters' => self::FILTERS, 'filter' => $filter, 'queue' => $queue,
            'b' => $detail, 'canQuote' => (bool)$canQuote, 'photos' => $detail ? $m->photos($id) : [],
            'free' => $free, 'busy' => $busy,
            'slots' => BookingController::SLOTS, 'levels' => self::LEVELS,
            'old' => $old, 'errors' => $errors, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    private function validate(array $in): array
    {
        $e = [];
        if (!isset(self::LEVELS[$in['condition_level']])) {
            $e['condition_level'] = 'Select the vehicle condition.';
        }
        $price = str_replace(',', '', $in['quoted_price']);
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $price) || (float)$price <= 0) {
            $e['quoted_price'] = 'Enter a price greater than 0, for example 6500.';
        }
        if (mb_strlen($in['remarks']) > 1000) {
            $e['remarks'] = 'Remarks are too long (1000 characters at most).';
        }
        if (!ctype_digit($in['cleaner_id']) || (int)$in['cleaner_id'] <= 0) {
            $e['cleaner_id'] = 'Select a cleaner.';
        }
        return $e;
    }
}
