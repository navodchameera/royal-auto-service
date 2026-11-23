<?php
defined('APP_PATH') or exit;

class AccountController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function settings(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }

        $session = $_SESSION['user'];
        $id      = (int)$session['id'];
        $account = $this->users->findById($id);
        if (!$account) { $this->redirect('logout'); }

        $errors = [];
        $old    = ['username' => $account['username'], 'address' => (string)$account['address']];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } elseif ($action === 'username') {
                $old['username'] = trim($_POST['username'] ?? '');
                $errors = $this->changeUsername($id, $account, $old['username']);
                if (!$errors) { $this->done('Username updated.'); }
            } elseif ($action === 'password') {
                $errors = $this->changePassword($id, $account);
                if (!$errors) { session_regenerate_id(true); $this->done('Password changed.'); }
            } elseif ($action === 'address' && $session['role'] === 'customer') {
                // Only customers have an address they can edit
                $old['address'] = trim($_POST['address'] ?? '');
                $errors = $this->changeAddress($id, $old['address']);
                if (!$errors) { $this->done('Address updated.'); }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('account/settings', [
            'user' => $session, 'account' => $account, 'errors' => $errors,
            'old'  => $old,     'flash'   => $flash,   'csrf'   => $this->csrfToken(),
        ]);
    }

    private function done(string $message): void
    {
        $_SESSION['flash'] = $message;
        $this->redirect('settings');
    }

    private function changeUsername(int $id, array $account, string $username): array
    {
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
            return ['username' => 'Use 3 to 30 letters, numbers, dots or underscores.'];
        }
        if ($username === $account['username']) {
            return ['username' => 'This is already your username.'];
        }
        if ($this->users->usernameTaken($username, $id)) {
            return ['username' => 'This username is taken.'];
        }
        $this->users->updateUsername($id, $username);
        return [];
    }

    private function changePassword(int $id, array $account): array
    {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $e       = [];

        if (!password_verify($current, $account['password'])) {
            $e['current_password'] = 'Your current password is incorrect.';
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $new)) {
            $e['new_password'] = 'Use at least 8 characters with a letter and a number.';
        } elseif ($new === $current) {
            $e['new_password'] = 'Choose a password different from your current one.';
        }
        if ($confirm !== $new) {
            $e['confirm_password'] = 'Passwords do not match.';
        }
        if (!$e) { $this->users->updatePassword($id, $new); }
        return $e;
    }

    private function changeAddress(int $id, string $address): array
    {
        if ($address === '') { return ['address' => 'Enter your address.']; }
        if (mb_strlen($address) > 255) { return ['address' => 'Address is too long.']; }
        $this->users->updateAddress($id, $address);
        return [];
    }
}
