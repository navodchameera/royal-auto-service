<?php
defined('APP_PATH') or exit;

class PaymentController extends Controller
{
    // Payments page: what is still to pay, and the payment history
    public function index(): void
    {
        $user = $this->requireRole('customer');
        $uid  = (int)$user['id'];
        $pay  = new Payment();

        $due     = $pay->due($uid);
        $history = $pay->history($uid);

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('customer/payments', [
            'user' => $user, 'due' => $due, 'history' => $history,
            'dueTotal' => array_sum(array_column($due, 'quoted_price')),
            'paidTotal' => array_sum(array_column($history, 'amount')),
            'flash' => $flash,
        ]);
    }

    // Demo payment gateway. It checks the card details but never charges or stores anything.
    public function pay(): void
    {
        $user = $this->requireRole('customer');
        $uid  = (int)$user['id'];
        $pay  = new Payment();

        $id = (int)($_POST['booking_id'] ?? $_GET['id'] ?? 0);
        $booking = $pay->payable($id, $uid);
        if (!$booking) {
            $_SESSION['flash'] = 'That booking cannot be paid online. It may already be paid.';
            $this->redirect('payments');
        }

        $old    = ['card_name' => '', 'card_expiry' => ''];   // the card number and CVV are never sent back
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['card_name']   = trim((string)($_POST['card_name'] ?? ''));
            $old['card_expiry'] = trim((string)($_POST['card_expiry'] ?? ''));

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validateCard($old);
                if (!$errors) {
                    $result = $pay->record($id, 'online', ['assigned', 'in_progress', 'completed'], $uid, null);
                    if ($result === '') {
                        $_SESSION['flash'] = 'Payment of ' . money($booking['quoted_price']) . ' received for booking #' . $id
                            . '. This was a demo payment, so no real card was charged.';
                        $this->redirect('payments');
                    }
                    $errors['form'] = $result;
                }
            }
        }

        $this->view('customer/pay', [
            'user' => $user, 'b' => $booking, 'old' => $old, 'errors' => $errors, 'csrf' => $this->csrfToken(),
        ]);
    }

    private function validateCard(array $in): array
    {
        $e = [];

        if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,59}$/", $in['card_name'])) {
            $e['card_name'] = 'Enter the name on the card.';
        }

        $number = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
        if (!preg_match('/^\d{13,19}$/', $number)) {
            $e['card_number'] = 'Enter a valid card number.';
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $in['card_expiry'], $m)) {
            $e['card_expiry'] = 'Enter the expiry date as MM/YY.';
        } else {
            $year = 2000 + (int)$m[2];
            $now  = new DateTime();
            if ($year < (int)$now->format('Y') || ($year === (int)$now->format('Y') && (int)$m[1] < (int)$now->format('n'))) {
                $e['card_expiry'] = 'This card has expired.';
            }
        }

        if (!preg_match('/^\d{3,4}$/', (string)($_POST['card_cvv'] ?? ''))) {
            $e['card_cvv'] = 'Enter the 3 or 4 digit security code.';
        }

        return $e;
    }
}
