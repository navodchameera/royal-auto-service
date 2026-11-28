<?php
defined('APP_PATH') or exit;

// Manager: business reports for a chosen period, to read, print or download (CSV / PDF)
class ManageReportsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('manager');

        $type = (string)($_GET['report'] ?? 'overview');
        if (!isset(Reports::TYPES[$type])) { $type = 'overview'; }

        $key = (string)($_GET['period'] ?? 'last30');
        if (!isset(Reports::PERIODS[$key])) { $key = 'last30'; }
        $period = Reports::period($key, trim((string)($_GET['from'] ?? '')), trim((string)($_GET['to'] ?? '')));

        $report = (new Reports())->build($type, $period['from'], $period['to']);

        $download = (string)($_GET['download'] ?? '');
        if ($period['error'] === '' && in_array($download, ['csv', 'pdf'], true)) {
            $name = 'Royal-Auto-' . $type . '-report-' . $period['from'] . '_to_' . $period['to'];
            if ($download === 'csv') {
                $body = ReportExport::csv($report, $period);
                header('Content-Type: text/csv; charset=utf-8');
            } else {
                $body = ReportExport::pdf($report, $period);
                header('Content-Type: application/pdf');
            }
            header('Content-Disposition: attachment; filename="' . $name . '.' . $download . '"');
            header('Content-Length: ' . strlen($body));
            header('X-Content-Type-Options: nosniff');
            echo $body;
            exit;
        }

        $this->view('manager/reports', [
            'user' => $user, 'type' => $type, 'period' => $period, 'report' => $report,
            'customFrom' => $key === 'custom' ? $_GET['from'] ?? '' : $period['from'],
            'customTo'   => $key === 'custom' ? $_GET['to'] ?? '' : $period['to'],
        ]);
    }
}
