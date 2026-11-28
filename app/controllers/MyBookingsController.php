<?php
defined('APP_PATH') or exit;

// The customer's My bookings page
class MyBookingsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('customer');
        $uid  = (int)$user['id'];

        $filters = ['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        $filter  = (string)($_GET['filter'] ?? 'all');
        if (!isset($filters[$filter])) { $filter = 'all'; }

        $my       = new MyBookings();
        $error    = '';
        $reviewId = 0;                       // booking whose review form had a problem
        $reviewErrors = [];
        $reviewOld = ['rating' => '', 'comment' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int)($_POST['booking_id'] ?? 0);
            $action = (string)($_POST['action'] ?? '');
            $back   = 'bookings&filter=' . $filter;

            if (!$this->csrfValid()) {
                $error = 'Your session expired. Please try again.';
            } elseif ($action === 'cancel') {
                if ($my->cancel($id, $uid)) {
                    $_SESSION['flash'] = 'Booking #' . $id . ' was cancelled.';
                    $this->redirect($back);
                }
                $error = 'This booking can no longer be cancelled.';
            } elseif ($action === 'review') {
                $reviewId = $id;
                $reviewOld = ['rating' => trim((string)($_POST['rating'] ?? '')), 'comment' => trim((string)($_POST['comment'] ?? ''))];

                if (!preg_match('/^[1-5]$/', $reviewOld['rating'])) {
                    $reviewErrors['rating'] = 'Choose a rating from 1 to 5.';
                }
                if (mb_strlen($reviewOld['comment']) < 3) {
                    $reviewErrors['comment'] = 'Write a short review (at least 3 characters).';
                } elseif (mb_strlen($reviewOld['comment']) > 1000) {
                    $reviewErrors['comment'] = 'Your review is too long (1000 characters at most).';
                }
                if (!$reviewErrors) {
                    $result = $my->review($id, $uid, (int)$reviewOld['rating'], $reviewOld['comment']);
                    if ($result === '') {
                        $_SESSION['flash'] = 'Thank you for your review. You can now complete booking #' . $id . '.';
                        $this->redirect($back);
                    }
                    $error = $result;
                }
            } elseif ($action === 'finish') {
                $result = $my->finish($id, $uid);
                if ($result === '') {
                    $_SESSION['flash'] = 'Booking #' . $id . ' is completed. Thank you!';
                    $this->redirect($back);
                }
                $error = $result;
            } else {
                $error = 'That action is not available.';
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('customer/bookings', [
            'user' => $user, 'filters' => $filters, 'filter' => $filter,
            'bookings' => $my->listForUser($uid, $filter),
            'error' => $error, 'flash' => $flash, 'csrf' => $this->csrfToken(),
            'reviewId' => $reviewId, 'reviewErrors' => $reviewErrors, 'reviewOld' => $reviewOld,
        ]);
    }
}
