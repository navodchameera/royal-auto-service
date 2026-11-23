<?php
defined('APP_PATH') or exit;

class BookingController extends Controller
{
    // Photo slots shown on the form (key is saved as photo_area)
    public const SLOTS = [
        'front'          => 'Front',
        'rear'           => 'Rear',
        'left'           => 'Left side',
        'interior_front' => 'Interior front',
        'rear_seats'     => 'Rear seats',
        'boot'           => 'Boot',
    ];

    public const TYPES = ['car' => 'Car', 'suv' => 'SUV', 'van' => 'Van', 'bike' => 'Bike', 'lorry' => 'Lorry', 'other' => 'Other'];

    public function create(): void
    {
        if (!isset($_SESSION['user'])) { $this->redirect('login'); }
        $user = $_SESSION['user'];
        if ($user['role'] !== 'customer') { $this->redirect('home'); }

        $uid      = (int)$user['id'];
        $vehicles = new Vehicle();
        $myCars   = $vehicles->forUser($uid);
        $packages = (new Package())->all();
        $account  = (new User())->findById($uid);

        $old = [
            'vehicle_id'       => $myCars ? (string)$myCars[0]['vehicle_id'] : 'new',
            'nv_type'          => 'car',
            'nv_make_model'    => '',
            'nv_plate'         => '',
            'nv_colour'        => '',
            'package_id'       => $packages ? (string)$packages[0]['package_id'] : '',
            'location_type'    => 'garage',
            'job_address'      => (string)($account['address'] ?? ''),
            'appointment_date' => '',
            'appointment_time' => '',
        ];

        // Links like ...?url=book&vehicle=3 or &vehicle=new preselect the vehicle
        $pick = (string)($_GET['vehicle'] ?? '');
        if ($pick === 'new' || (ctype_digit($pick) && $vehicles->belongsTo((int)$pick, $uid))) {
            $old['vehicle_id'] = $pick;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $key => $default) {
                $old[$key] = trim((string)($_POST[$key] ?? $default));
            }

            if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
                // The upload was bigger than the server allows, so PHP dropped everything
                $errors['form'] = 'Your photos are too large in total. Try fewer or smaller photos.';
            } elseif (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                $errors = $this->validate($old, $uid, $vehicles, $packages);
            }

            if (!$errors) {
                $bookingId = $this->store($old, $uid, $vehicles);
                if ($bookingId) {
                    $_SESSION['flash'] = 'Booking #' . $bookingId . ' submitted. We will send your quotation after reviewing the photos.';
                    $this->redirect('home');
                }
                $errors['form'] = 'We could not save your booking. Please try again.';
            }
        }

        $this->view('customer/book', [
            'user' => $user, 'vehicles' => $myCars, 'packages' => $packages,
            'slots' => self::SLOTS, 'types' => self::TYPES,
            'old' => $old, 'errors' => $errors,
            'today' => date('Y-m-d'), 'csrf' => $this->csrfToken(),
        ]);
    }

    // Server-side validation (the JavaScript checks can be bypassed)
    private function validate(array $in, int $uid, Vehicle $vm, array $packages): array
    {
        $e = [];

        if ($in['vehicle_id'] === 'new') {
            if (!isset(self::TYPES[$in['nv_type']])) {
                $e['nv_type'] = 'Choose a vehicle type.';
            }
            if (mb_strlen($in['nv_make_model']) < 2 || mb_strlen($in['nv_make_model']) > 100) {
                $e['nv_make_model'] = 'Enter the make and model.';
            }
            if (!preg_match('/^[A-Za-z0-9 -]{3,15}$/', $in['nv_plate'])) {
                $e['nv_plate'] = 'Enter a valid plate number.';
            } elseif ($vm->plateExists(strtoupper($in['nv_plate']))) {
                $e['nv_plate'] = 'This plate number is already registered.';
            }
            if (mb_strlen($in['nv_colour']) > 30) {
                $e['nv_colour'] = 'Colour is too long.';
            }
        } elseif (!ctype_digit($in['vehicle_id']) || !$vm->belongsTo((int)$in['vehicle_id'], $uid)) {
            $e['vehicle_id'] = 'Choose one of your vehicles.';
        }

        $ids = array_map('strval', array_column($packages, 'package_id'));
        if (!in_array($in['package_id'], $ids, true)) {
            $e['package_id'] = 'Choose a package.';
        }

        if (!in_array($in['location_type'], ['garage', 'customer_site'], true)) {
            $e['location_type'] = 'Choose where we should wash it.';
        } elseif ($in['location_type'] === 'customer_site') {
            if ($in['job_address'] === '') {
                $e['job_address'] = 'Enter the address for this booking.';
            } elseif (mb_strlen($in['job_address']) > 255) {
                $e['job_address'] = 'Address is too long.';
            }
        }

        $today = date('Y-m-d');
        $date  = DateTime::createFromFormat('Y-m-d', $in['appointment_date']);
        if (!$date || $date->format('Y-m-d') !== $in['appointment_date']) {
            $e['appointment_date'] = 'Choose a date.';
        } elseif ($in['appointment_date'] < $today) {
            $e['appointment_date'] = 'Choose today or a later date.';
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['appointment_time'])) {
            $e['appointment_time'] = 'Choose a time.';
        } elseif (!isset($e['appointment_date']) && $in['appointment_date'] === $today && $in['appointment_time'] <= date('H:i')) {
            $e['appointment_time'] = 'Choose a later time today.';
        }

        // Photos: optional at our garage, at least one for customer site jobs
        $count = 0;
        $photoErrors = [];
        foreach (self::SLOTS as $key => $label) {
            $r = PhotoUploader::inspect($_FILES['photo_' . $key] ?? null);
            if ($r['empty']) { continue; }
            if ($r['error']) { $photoErrors[] = $label . ' photo ' . $r['error'] . '.'; } else { $count++; }
        }
        if ($photoErrors) {
            $e['photos'] = $photoErrors;
        } elseif ($in['location_type'] === 'customer_site' && $count === 0) {
            $e['photos'] = ['Add at least one photo so we can quote the price before we visit.'];
        }

        return $e;
    }

    // Saves vehicle (if new), booking and photos together; undoes everything on failure
    private function store(array $in, int $uid, Vehicle $vm): ?int
    {
        $pdo   = Database::connect();
        $saved = [];

        try {
            $pdo->beginTransaction();

            $vehicleId = $in['vehicle_id'] === 'new'
                ? $vm->create($uid, [
                    'vehicle_type' => $in['nv_type'],
                    'make_model'   => $in['nv_make_model'],
                    'plate_no'     => strtoupper($in['nv_plate']),
                    'colour'       => $in['nv_colour'],
                ])
                : (int)$in['vehicle_id'];

            $bookingId = (new Booking())->create([
                'user_id'          => $uid,
                'vehicle_id'       => $vehicleId,
                'package_id'       => (int)$in['package_id'],
                'location_type'    => $in['location_type'],
                'job_address'      => $in['location_type'] === 'customer_site' ? $in['job_address'] : null,
                'appointment_date' => $in['appointment_date'],
                'appointment_time' => $in['appointment_time'],
            ]);

            $photos = new Photo();
            foreach (self::SLOTS as $key => $label) {
                $file = $_FILES['photo_' . $key] ?? null;
                $r    = PhotoUploader::inspect($file);
                if ($r['empty']) { continue; }
                $name    = PhotoUploader::store($file, $r['ext']);
                $saved[] = $name;
                $photos->add($bookingId, $name, $key);
            }

            $pdo->commit();
            return $bookingId;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            foreach ($saved as $name) { @unlink(PhotoUploader::dir() . $name); }
            return null;
        }
    }
}
