<?php
defined('APP_PATH') or exit;

class ManageBookingsController extends Controller
{
    public const STATUSES = [
        'all' => 'All', 'requested' => 'Requested', 'quoted' => 'Quoted', 'accepted' => 'Accepted',
        'assigned' => 'Assigned', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
    ];

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

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/bookings', [
            'user' => $user, 'statuses' => self::STATUSES, 'status' => $status, 'q' => $q,
            'bookings' => $m->bookings($status, $q), 'flash' => $flash,
        ]);
    }

    private function detail(array $user, Manage $m, int $id): void
    {
        $booking = $m->booking($id);
        if (!$booking) {
            $_SESSION['flash'] = 'Booking #' . $id . ' was not found.';
            $this->redirect('manage-bookings');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrfValid()) {
                $error = 'Your session expired. Please try again.';
            } elseif (($_POST['action'] ?? '') === 'cancel' && $m->cancelBooking($id)) {
                $_SESSION['flash'] = 'Booking #' . $id . ' was cancelled.';
                $this->redirect('manage-bookings&id=' . $id);
            } else {
                $error = 'This booking can no longer be cancelled.';
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/booking_detail', [
            'user' => $user, 'b' => $booking, 'photos' => $m->photos($id),
            'slots' => BookingController::SLOTS, 'error' => $error, 'flash' => $flash,
            'csrf' => $this->csrfToken(),
        ]);
    }
}
