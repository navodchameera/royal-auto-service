<?php
defined('APP_PATH') or exit;

class HomeController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }

        $user = $_SESSION['user'];

        // Manager and cleaner homes are designed but not built yet
        if ($user['role'] !== 'customer') {
            $this->view('home', ['user' => $user]);
            return;
        }

        $id       = (int)$user['id'];
        $bookings = new Booking();
        $vehicles = new Vehicle();

        $this->view('customer/home', [
            'user'      => $user,
            'next'      => $bookings->nextAppointment($id),
            'attention' => $bookings->attention($id),
            'vehicles'  => $vehicles->forUser($id),
            'recent'    => $bookings->recent($id, 5),
        ]);
    }
}
