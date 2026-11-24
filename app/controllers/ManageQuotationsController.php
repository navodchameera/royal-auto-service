<?php
defined('APP_PATH') or exit;

class ManageQuotationsController extends Controller
{
    private const FILTERS = ['requested' => 'To quote', 'quoted' => 'Sent to customer'];
    private const LEVELS  = ['light' => 'Light', 'moderate' => 'Moderate', 'heavy' => 'Heavy'];

    public function index(): void
    {
        $user = $this->requireRole('manager');
        $m    = new Manage();

        $filter = (string)($_GET['filter'] ?? 'requested');
        if (!isset(self::FILTERS[$filter])) { $filter = 'requested'; }

        $errors = [];
        $old    = ['condition_level' => '', 'quoted_price' => '', 'remarks' => ''];
        $id     = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['booking_id'] ?? 0);
            foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validate($old);
                if (!$errors) {
                    $price = number_format((float)str_replace(',', '', $old['quoted_price']), 2, '.', '');
                    if ($m->saveQuotation($id, $price, $old['condition_level'], $old['remarks'])) {
                        $_SESSION['flash'] = 'Quotation for booking #' . $id . ' was sent to the customer.';
                        $this->redirect('manage-quotations&filter=requested');
                    }
                    $errors['form'] = 'This booking can no longer be quoted. It may have been cancelled.';
                }
            }
        }

        $queue = $m->quoteQueue($filter);
        if (!$id && $queue) { $id = (int)$queue[0]['booking_id']; }

        $detail = $id ? $m->booking($id) : null;
        $canQuote = $detail && in_array($detail['status'], ['requested', 'quoted'], true);

        // Show the quotation already sent when re-opening a quoted booking
        if ($detail && $canQuote && $_SERVER['REQUEST_METHOD'] !== 'POST' && $detail['quoted_price'] !== null) {
            $old = [
                'condition_level' => (string)$detail['condition_level'],
                'quoted_price'    => (string)(float)$detail['quoted_price'],
                'remarks'         => (string)$detail['remarks'],
            ];
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/quotations', [
            'user' => $user, 'filters' => self::FILTERS, 'filter' => $filter, 'queue' => $queue,
            'b' => $detail, 'canQuote' => (bool)$canQuote, 'photos' => $detail ? $m->photos($id) : [],
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
        return $e;
    }
}
