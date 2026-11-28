<?php
defined('APP_PATH') or exit;

// The cleaner's pages: Dashboard (today's jobs) and My jobs (every job assigned to them)
class CleanerJobsController extends Controller
{
    // Dashboard: today's jobs
    public function dashboard(): void
    {
        $user = $this->requireRole('cleaner');
        $uid  = (int)$user['id'];

        $error = $this->handleAction($uid, 'home');

        $all   = (new Jobs())->forCleaner($uid, 'all');
        $today = date('Y-m-d');

        $todayJobs = array_values(array_filter($all, function ($j) use ($today) {
            return $j['appointment_date'] === $today && $j['status'] !== 'cancelled';
        }));
        usort($todayJobs, function ($a, $b) { return strcmp($a['appointment_time'], $b['appointment_time']); });

        $active = array_values(array_filter($all, function ($j) { return in_array($j['status'], ['assigned', 'in_progress'], true); }));

        // Jobs on later days, soonest first
        $later = array_values(array_filter($active, function ($j) use ($today) { return $j['appointment_date'] > $today; }));
        usort($later, function ($a, $b) {
            return strcmp($a['appointment_date'] . $a['appointment_time'], $b['appointment_date'] . $b['appointment_time']);
        });

        $toCollect = 0;
        foreach ($active as $j) { if (!$j['pay_method'] && $j['quoted_price'] !== null) { $toCollect++; } }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('cleaner/home', [
            'user' => $user, 'todayJobs' => $todayJobs, 'laterCount' => count($later),
            'next' => $later ? $later[0] : null, 'toCollect' => $toCollect,
            'error' => $error, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    // My jobs: everything assigned to this cleaner, with filters
    public function index(): void
    {
        $user = $this->requireRole('cleaner');
        $uid  = (int)$user['id'];

        $bid = (string)($_GET['id'] ?? '');
        if (ctype_digit($bid) && (int)$bid > 0) {
            $this->detail($user, (int)$bid);
            return;
        }

        $filters = ['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        $filter  = (string)($_GET['filter'] ?? 'all');
        if (!isset($filters[$filter])) { $filter = 'all'; }

        $error = $this->handleAction($uid, 'my-jobs&filter=' . $filter);

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('cleaner/jobs', [
            'user' => $user, 'filters' => $filters, 'filter' => $filter,
            'jobs' => (new Jobs())->forCleaner($uid, $filter),
            'error' => $error, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    // One job with every detail of its booking
    private function detail(array $user, int $bookingId): void
    {
        $uid = (int)$user['id'];
        $job = (new Jobs())->detail($bookingId, $uid);
        if (!$job) {
            $_SESSION['flash'] = 'Job #' . $bookingId . ' was not found.';
            $this->redirect('my-jobs');
        }

        $error = $this->handleAction($uid, 'my-jobs&id=' . $bookingId);

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('cleaner/job_detail', [
            'user' => $user, 'j' => (new Jobs())->detail($bookingId, $uid), 'photos' => (new Manage())->photos($bookingId),
            'slots' => BookingController::SLOTS, 'error' => $error, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    // Start / Mark as paid (cash) / Complete. Redirects to $page on success, otherwise returns a message.
    private function handleAction(int $uid, string $page): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return ''; }
        if (!$this->csrfValid()) { return 'Your session expired. Please try again.'; }

        $jobs   = new Jobs();
        $id     = (int)($_POST['booking_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'start') {
            if ($jobs->start($id, $uid)) {
                $_SESSION['flash'] = 'Job #' . $id . ' started.';
                $this->redirect($page);
            }
            return 'This job cannot be started.';
        }

        if ($action === 'pay') {
            // The customer has not paid online, so the cleaner takes cash
            $result = (new Payment())->record($id, 'cash', ['assigned', 'in_progress'], null, $uid);
            if ($result === '') {
                $_SESSION['flash'] = 'Job #' . $id . ' marked as paid (cash).';
                $this->redirect($page);
            }
            return $result;
        }

        if ($action === 'complete') {
            if ($jobs->complete($id, $uid)) {
                $_SESSION['flash'] = 'Job #' . $id . ' completed. The customer will now rate and confirm it.';
                $this->redirect($page);
            }
            return 'A job can only be completed after it has been started and paid.';
        }

        return 'That action is not available for this job.';
    }
}
