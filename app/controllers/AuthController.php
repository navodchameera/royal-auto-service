<?php
defined('APP_PATH') or exit;
class AuthController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function login(): void
    {
        if (isset($_SESSION['user'])) { $this->redirect('home'); }

        $errors = [];
        $old    = ['login' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['login'] = trim($_POST['login'] ?? '');
            $password     = $_POST['password'] ?? '';

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } elseif ($old['login'] === '' || $password === '') {
                $errors['form'] = 'Enter your username or email and your password.';
            } else {
                $user = $this->users->findByLogin($old['login']);
                if (!$user || !password_verify($password, $user['password'])) {
                    $errors['form'] = 'Incorrect username, email or password.';
                } elseif ($user['status'] !== 'active') {
                    $errors['form'] = 'This account is blocked. Please contact the manager.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'   => $user['user_id'],
                        'name' => $user['full_name'],
                        'role' => $user['role'],
                    ];
                    $this->redirect('home');
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('auth/login', [
            'errors' => $errors, 'old' => $old,
            'flash'  => $flash,  'csrf' => $this->csrfToken(),
        ]);
    }

    public function register(): void
    {
        if (isset($_SESSION['user'])) { $this->redirect('home'); }

        $errors = [];
        $old    = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'address' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $key => $_) {
                $old[$key] = trim($_POST[$key] ?? '');
            }
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validateRegister($old, $password, $confirm);
            }

            if (!$errors) {
                $old['password'] = $password;
                $this->users->create($old);
                $_SESSION['flash'] = 'Account created. Please log in.';
                $this->redirect('login');
            }
        }

        $this->view('auth/register', [
            'errors' => $errors, 'old' => $old, 'csrf' => $this->csrfToken(),
        ]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('login');
    }

    // Server-side validation (never trust the JavaScript checks alone)
    private function validateRegister(array $in, string $password, string $confirm): array
    {
        $e = [];
        if (mb_strlen($in['full_name']) < 2 || mb_strlen($in['full_name']) > 100) {
            $e['full_name'] = 'Enter your full name.';
        }
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $in['username'])) {
            $e['username'] = 'Use 3 to 30 letters, numbers, dots or underscores.';
        } elseif ($this->users->findByUsername($in['username'])) {
            $e['username'] = 'This username is taken.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid email address.';
        } elseif ($this->users->findByEmail($in['email'])) {
            $e['email'] = 'This email is already registered.';
        }
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $in['phone']))) {
            $e['phone'] = 'Enter a valid phone number.';
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
            $e['password'] = 'Use at least 8 characters with a letter and a number.';
        }
        if ($confirm !== $password) {
            $e['confirm_password'] = 'Passwords do not match.';
        }
        if ($in['address'] === '') {
            $e['address'] = 'Enter your address.';
        } elseif (mb_strlen($in['address']) > 255) {
            $e['address'] = 'Address is too long.';
        }
        if (!isset($_POST['terms'])) {
            $e['terms'] = 'You must agree to the Terms and Conditions.';
        }
        return $e;
    }
}
