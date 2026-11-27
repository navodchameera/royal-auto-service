<?php
defined('APP_PATH') or exit;

class ManageCustomersController extends Controller
{
    private const STATUSES = ['all' => 'All', 'active' => 'Active', 'blocked' => 'Banned'];

    public function index(): void
    {
        $user = $this->requireRole('manager');
        $m    = new Manage();

        $id = (string)($_GET['id'] ?? '');
        if (ctype_digit($id) && (int)$id > 0) {
            $this->detail($user, $m, (int)$id);
            return;
        }

        $status = (string)($_GET['status'] ?? 'all');
        if (!isset(self::STATUSES[$status])) { $status = 'all'; }
        $q = trim((string)($_GET['q'] ?? ''));

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = $this->statusAction($m);
            if ($error === '') {
                $this->redirect('manage-customers&status=' . $status . '&q=' . urlencode($q));
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/customers', [
            'user' => $user, 'statuses' => self::STATUSES, 'status' => $status, 'q' => $q,
            'customers' => $m->customers($status, $q), 'error' => $error, 'flash' => $flash,
            'csrf' => $this->csrfToken(),
        ]);
    }

    private function detail(array $user, Manage $m, int $id): void
    {
        $customer = $m->customer($id);
        if (!$customer) {
            $_SESSION['flash'] = 'Customer not found.';
            $this->redirect('manage-customers');
        }

        $errors = [];
        $old = [
            'full_name' => $customer['full_name'], 'email' => $customer['email'],
            'phone' => $customer['phone'], 'address' => (string)$customer['address'],
        ];
        $formError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'update') {
                if (!$this->csrfValid()) {
                    $formError = 'Your session expired. Please try again.';
                } else {
                    foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }
                    $errors = $this->validate($old, $m, $id);
                    if (!$errors) {
                        $m->updateCustomer($id, $old);
                        $_SESSION['flash'] = 'Customer details updated.';
                        $this->redirect('manage-customers&id=' . $id);
                    }
                }
            } else {
                $formError = $this->statusAction($m);
                if ($formError === '') {
                    // a deleted customer has no page any more
                    $this->redirect(($_POST['action'] ?? '') === 'delete' ? 'manage-customers' : 'manage-customers&id=' . $id);
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/customer_detail', [
            'user' => $user, 'c' => $customer, 'old' => $old, 'errors' => $errors,
            'formError' => $formError, 'flash' => $flash, 'csrf' => $this->csrfToken(),
            'vehicles' => $m->customerVehicles($id), 'recent' => $m->customerBookings($id, 5),
            'activeBookings' => $m->customerActiveBookings($id),
        ]);
    }

    // Ban, unban or delete a customer. Returns an error message, or '' on success (a flash is set)
    private function statusAction(Manage $m): string
    {
        if (!$this->csrfValid()) { return 'Your session expired. Please try again.'; }

        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['customer_id'] ?? 0);
        if (!in_array($action, ['ban', 'unban', 'delete'], true) || $id <= 0) { return 'Unknown action.'; }

        $customer = $m->customer($id);
        if (!$customer) { return 'Customer not found.'; }

        if ($action === 'delete') {
            $result = $m->deleteUser($id, 'customer');
            if (!$result['ok']) { return $result['error']; }
            foreach ($result['files'] as $file) { @unlink(PhotoUploader::dir() . basename($file)); }
            $_SESSION['flash'] = $customer['full_name'] . ' was deleted.';
            return '';
        }

        $new = $action === 'ban' ? 'blocked' : 'active';
        if ($customer['status'] !== $new) { $m->setUserStatus($id, 'customer', $new); }
        $_SESSION['flash'] = $customer['full_name'] . ($action === 'ban' ? ' was banned.' : ' can log in again.');
        return '';
    }

    private function validate(array $in, Manage $m, int $id): array
    {
        $e = [];
        if (mb_strlen($in['full_name']) < 2 || mb_strlen($in['full_name']) > 100) {
            $e['full_name'] = 'Enter the full name.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid email address.';
        } elseif ($m->emailTaken($in['email'], $id)) {
            $e['email'] = 'This email is already registered.';
        }
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $in['phone']))) {
            $e['phone'] = 'Enter a valid phone number.';
        }
        if ($in['address'] === '') {
            $e['address'] = 'Enter the address.';
        } elseif (mb_strlen($in['address']) > 255) {
            $e['address'] = 'Address is too long.';
        }
        return $e;
    }
}
