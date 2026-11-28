<?php
defined('APP_PATH') or exit;

class HomeController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }

        $user = $_SESSION['user'];

        if ($user['role'] === 'manager') {
            $dash = new Dashboard();
            $this->view('manager/home', [
                'user'     => $user,
                'counts'   => $dash->counts(),
                'schedule' => $dash->scheduleToday(),
                'cleaners' => $dash->cleanersToday(),
                'reviews'  => $dash->latestReviews(3),
            ]);
            return;
        }

        if ($user['role'] === 'cleaner') {
            (new CleanerJobsController())->dashboard();
            return;
        }

        if ($user['role'] !== 'customer') {
            $this->view('home', ['user' => $user]);
            return;
        }

        $id       = (int)$user['id'];
        $bookings = new Booking();
        $vehicles = new Vehicle();

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('customer/home', [
            'flash'     => $flash,
            'user'      => $user,
            'next'      => $bookings->nextAppointment($id),
            'attention' => (new MyBookings())->attention($id),
            'vehicles'  => $vehicles->forUser($id),
            'recent'    => $bookings->recent($id, 5),
        ]);
    }
}
