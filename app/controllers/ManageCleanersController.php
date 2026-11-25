<?php
defined('APP_PATH') or exit;

class ManageCleanersController extends Controller
{
    public function index(): void
    {
        $user  = $this->requireRole('manager');
        $m     = new Manage();
        $users = new User();

        $old    = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'address' => ''];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validate($old, $m, $users);
                if (!$errors) {
                    $m->createCleaner($old, DEFAULT_CLEANER_PASSWORD);
                    $_SESSION['flash'] = 'Account created for ' . $old['full_name'] . '. Username: ' . $old['username']
                        . ', password: ' . DEFAULT_CLEANER_PASSWORD . '. Ask them to change it in Settings.';
                    $this->redirect('manage-cleaners');
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/cleaners', [
            'user' => $user, 'cleaners' => $m->cleaners(), 'old' => $old, 'errors' => $errors,
            'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    private function validate(array $in, Manage $m, User $users): array
    {
        $e = [];
        if (mb_strlen($in['full_name']) < 2 || mb_strlen($in['full_name']) > 100) {
            $e['full_name'] = 'Enter the full name.';
        }
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $in['username'])) {
            $e['username'] = 'Use 3 to 30 letters, numbers, dots or underscores.';
        } elseif ($users->usernameTaken($in['username'], 0)) {
            $e['username'] = 'This username is taken.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid email address.';
        } elseif ($m->emailTaken($in['email'], 0)) {
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
