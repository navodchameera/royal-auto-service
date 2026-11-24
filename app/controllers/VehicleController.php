<?php
defined('APP_PATH') or exit;

class VehicleController extends Controller
{
    private const LOCKED = 'This vehicle is part of an active booking, so it cannot be edited until that booking is completed (or cancelled).';

    public function index(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }
        $user = $_SESSION['user'];
        if ($user['role'] !== 'customer') { $this->redirect('home'); }

        $uid = (int)$user['id'];
        $vm  = new Vehicle();

        // What the visitor last submitted (so the right form can show its errors)
        $blank     = ['vehicle_type' => '', 'make_model' => '', 'plate_no' => '', 'colour' => ''];
        $state     = ['mode' => '', 'id' => 0, 'old' => $blank, 'errors' => []];
        $formError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $id     = (int)($_POST['vehicle_id'] ?? 0);

            if (!$this->csrfValid()) {
                $formError = 'Your session expired. Please try again.';
            } elseif ($action === 'delete') {
                if (!$vm->find($id, $uid)) {
                    $formError = 'Vehicle not found.';
                } elseif ($vm->hasBookings($id)) {
                    $formError = 'This vehicle has bookings, so it cannot be deleted.';
                } else {
                    $vm->delete($id, $uid);
                    $this->done('Vehicle deleted.');
                }
            } elseif ($action === 'add' || $action === 'update') {
                $old = $blank;
                foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }
                $old['plate_no'] = strtoupper($old['plate_no']);

                if ($action === 'update') {
                    if (!$vm->find($id, $uid)) {
                        $formError = 'Vehicle not found.';
                    } elseif ($vm->hasActiveBooking($id)) {
                        $formError = self::LOCKED;
                    }
                }

                if ($formError === '') {
                    $errors = $this->validate($old, $vm, $action === 'update' ? $id : 0);
                    if ($errors) {
                        $state = ['mode' => $action, 'id' => $id, 'old' => $old, 'errors' => $errors];
                    } elseif ($action === 'update') {
                        $vm->update($id, $uid, $old);
                        $this->done('Vehicle updated.');
                    } else {
                        $vm->create($uid, $old);
                        $this->done('Vehicle added.');
                    }
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('customer/vehicles', [
            'user' => $user, 'vehicles' => $vm->forUserWithCounts($uid),
            'types' => BookingController::TYPES, 'state' => $state, 'formError' => $formError,
            'openAdd' => isset($_GET['add']), 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }

    private function done(string $message): void
    {
        $_SESSION['flash'] = $message;
        $this->redirect('vehicles');
    }

    private function validate(array $in, Vehicle $vm, int $exceptId): array
    {
        $e = [];
        if (!isset(BookingController::TYPES[$in['vehicle_type']])) {
            $e['vehicle_type'] = 'Select a vehicle type.';
        }
        if (mb_strlen($in['make_model']) < 2 || mb_strlen($in['make_model']) > 100) {
            $e['make_model'] = 'Enter the make and model.';
        }
        if (!preg_match('/^[A-Za-z0-9 -]{3,15}$/', $in['plate_no'])) {
            $e['plate_no'] = 'Enter a valid plate number.';
        } elseif ($vm->plateExists($in['plate_no'], $exceptId)) {
            $e['plate_no'] = 'This plate number is already registered.';
        }
        if (mb_strlen($in['colour']) > 30) {
            $e['colour'] = 'Colour is too long.';
        }
        return $e;
    }
}
