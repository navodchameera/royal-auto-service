<?php
defined('APP_PATH') or exit;
class Controller
{
    protected function view(string $path, array $data = []): void
    {
        extract($data);
        require APP_PATH . '/views/' . $path . '.php';
    }

    protected function redirect(string $to): void
    {
        header('Location: ' . BASE_URL . '/index.php?url=' . $to);
        exit;
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    protected function csrfValid(): bool
    {
        return isset($_POST['csrf'], $_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }
}
