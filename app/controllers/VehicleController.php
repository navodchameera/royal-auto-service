<?php
defined('APP_PATH') or exit;

class VehicleController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }
        $user = $_SESSION['user'];
        if ($user['role'] !== 'customer') { $this->redirect('home'); }

        $uid = (int)$user['id'];
        $vm  = new Vehicle();

        // ?edit=ID opens the form in edit mode
        $editing = null;
        $editId  = (string)($_GET['edit'] ?? '');
        if (ctype_digit($editId)) { $editing = $vm->find((int)$editId, $uid); }

        $errors = [];
        $old = [
            'vehicle_type' => $editing['vehicle_type'] ?? 'car',
            'make_model'   => $editing['make_model'] ?? '',
            'plate_no'     => $editing['plate_no'] ?? '',
            'colour'       => (string)($editing['colour'] ?? ''),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $id     = (int)($_POST['vehicle_id'] ?? 0);

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } elseif ($action === 'delete') {
                if (!$vm->find($id, $uid)) {
                    $errors['form'] = 'Vehicle not found.';
                } elseif ($vm->hasBookings($id)) {
                    $errors['form'] = 'This vehicle has bookings, so it cannot be deleted.';
                } else {
                    $vm->delete($id, $uid);
                    $this->done('Vehicle deleted.');
                }
            } elseif ($action === 'add' || $action === 'update') {
                foreach ($old as $key => $_) { $old[$key] = trim((string)($_POST[$key] ?? '')); }
                $old['plate_no'] = strtoupper($old['plate_no']);

                $editing = null;
                if ($action === 'update') {
                    $editing = $vm->find($id, $uid);
                    if (!$editing) { $errors['form'] = 'Vehicle not found.'; }
                }

                if (!$errors) { $errors = $this->validate($old, $vm, $editing ? (int)$editing['vehicle_id'] : 0); }

                if (!$errors) {
                    if ($editing) {
                        $vm->update((int)$editing['vehicle_id'], $uid, $old);
                        $this->done('Vehicle updated.');
                    }
                    $vm->create($uid, $old);
                    $this->done('Vehicle added.');
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('customer/vehicles', [
            'user' => $user, 'vehicles' => $vm->forUserWithCounts($uid),
            'types' => BookingController::TYPES, 'editing' => $editing,
            'old' => $old, 'errors' => $errors, 'flash' => $flash, 'csrf' => $this->csrfToken(),
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
            $e['vehicle_type'] = 'Choose a vehicle type.';
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
