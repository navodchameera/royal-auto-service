<?php
defined('APP_PATH') or exit;

// The cleaner's home page: their jobs, with Start / Complete / Mark as paid (cash)
class CleanerJobsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('cleaner');
        $uid  = (int)$user['id'];
        $jobs = new Jobs();

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int)($_POST['booking_id'] ?? 0);
            $action = $_POST['action'] ?? '';

            if (!$this->csrfValid()) {
                $error = 'Your session expired. Please try again.';
            } elseif ($action === 'start' && $jobs->start($id, $uid)) {
                $_SESSION['flash'] = 'Job #' . $id . ' started.';
                $this->redirect('home');
            } elseif ($action === 'complete' && $jobs->complete($id, $uid)) {
                $_SESSION['flash'] = 'Job #' . $id . ' marked as completed.';
                $this->redirect('home');
            } elseif ($action === 'pay') {
                $result = (new Payment())->record($id, 'cash', ['completed'], null, $uid);
                if ($result === '') {
                    $_SESSION['flash'] = 'Job #' . $id . ' marked as paid (cash).';
                    $this->redirect('home');
                }
                $error = $result;
            } else {
                $error = 'That action is not available for this job.';
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $all = $jobs->forCleaner($uid);
        $this->view('cleaner/jobs', [
            'user'   => $user,
            'active' => array_values(array_filter($all, function ($j) { return $j['status'] !== 'completed'; })),
            'done'   => array_reverse(array_values(array_filter($all, function ($j) { return $j['status'] === 'completed'; }))),
            'error'  => $error, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }
}
