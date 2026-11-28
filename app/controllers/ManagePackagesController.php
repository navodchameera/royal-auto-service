<?php
defined('APP_PATH') or exit;

// Manager: the service packages and their price ranges.
// Packages cannot be deleted, because deleting one would also delete every booking that uses it.
class ManagePackagesController extends Controller
{
    public function index(): void
    {
        $user = $this->requireRole('manager');
        $m    = new ManageMore();

        $editId = (int)($_GET['edit'] ?? 0);       // 0 = none, -1 = new package
        $errors = [];
        $old    = ['name' => '', 'description' => '', 'min' => '', 'max' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $editId = (int)($_POST['package_id'] ?? 0);
            $old = [
                'name'        => trim((string)($_POST['package_name'] ?? '')),
                'description' => trim((string)($_POST['description'] ?? '')),
                'min'         => trim((string)($_POST['min_price'] ?? '')),
                'max'         => trim((string)($_POST['max_price'] ?? '')),
            ];

            if (!$this->csrfValid()) {
                $errors['form'] = 'Your session expired. Please try again.';
            } else {
                if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 50) {
                    $errors['name'] = 'Enter a package name (2 to 50 characters).';
                } elseif ($m->nameTaken($old['name'], max($editId, 0))) {
                    $errors['name'] = 'A package with this name already exists.';
                }
                if (mb_strlen($old['description']) > 255) {
                    $errors['description'] = 'The description is too long (255 characters at most).';
                }
                foreach (['min' => 'minimum', 'max' => 'maximum'] as $k => $word) {
                    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $old[$k]) || (float)$old[$k] <= 0) {
                        $errors[$k] = 'Enter the ' . $word . ' price as a number above 0.';
                    }
                }
                if (!$errors && (float)$old['min'] > (float)$old['max']) {
                    $errors['max'] = 'The maximum price must not be lower than the minimum.';
                }

                if (!$errors) {
                    $m->savePackage(max($editId, 0), $old);
                    $_SESSION['flash'] = $editId > 0 ? 'Package updated.' : 'Package added.';
                    $this->redirect('manage-packages');
                }
            }
        } elseif ($editId > 0) {
            foreach ($m->packages() as $p) {
                if ((int)$p['package_id'] === $editId) {
                    $old = ['name' => $p['package_name'], 'description' => (string)$p['description'],
                            'min' => (string)(float)$p['min_price'], 'max' => (string)(float)$p['max_price']];
                }
            }
        }

        $flash = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);

        $this->view('manager/packages', [
            'user' => $user, 'packages' => $m->packages(), 'editId' => $editId, 'old' => $old,
            'errors' => $errors, 'flash' => $flash, 'csrf' => $this->csrfToken(),
        ]);
    }
}
