<?php
defined('APP_PATH') or exit;

// Manager: every job and the cleaner doing it
class ManageJobsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('manager');

        $filters = ['all' => 'All', 'today' => 'Today', 'active' => 'Active', 'done' => 'Done', 'cancelled' => 'Cancelled'];
        $filter  = (string)($_GET['filter'] ?? 'today');
        if (!isset($filters[$filter])) { $filter = 'today'; }

        $this->view('manager/jobs', [
            'user' => $user, 'filters' => $filters, 'filter' => $filter, 'jobs' => (new ManageMore())->jobs($filter),
        ]);
    }
}
