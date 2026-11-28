<?php
defined('APP_PATH') or exit;

// Manager: money collected (online and cash) and what is still unpaid
class ManagePaymentsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('manager');

        $filters = ['all' => 'All payments', 'online' => 'Online', 'cash' => 'Cash', 'unpaid' => 'Unpaid'];
        $filter  = (string)($_GET['filter'] ?? 'all');
        if (!isset($filters[$filter])) { $filter = 'all'; }

        $m = new ManageMore();
        $this->view('manager/payments', [
            'user' => $user, 'filters' => $filters, 'filter' => $filter,
            'totals' => $m->paymentTotals(), 'rows' => $m->payments($filter),
        ]);
    }
}
