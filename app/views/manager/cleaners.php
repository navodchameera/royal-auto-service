<?php
defined('APP_PATH') or exit;
$title     = 'Cleaners';
$navActive = 'cleaners';
require APP_PATH . '/views/layout/app_header.php';
$err = function ($key) use ($errors) { return e($errors[$key] ?? ''); };
?>
<div class="top">
  <div>
    <h1>Cleaners</h1>
    <div class="sub">Create accounts for your cleaning staff.</div>
  </div>
  <button class="btn" type="button" data-open-dialog="addCleanerDialog">Add a cleaner</button>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if (!empty($errors['page'])): ?><div class="alert" role="alert"><?= e($errors['page']) ?></div><?php endif; ?>

<section class="card">
  <?php if ($cleaners): ?>
    <div class="tblwrap">
      <table class="tbl">
        <thead><tr><th>Cleaner</th><th>Contact</th><th>Address</th><th>Jobs</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cleaners as $c):
            $blocked = $c['status'] === 'blocked';
            $cid     = (int)$c['user_id'];
            $name    = $c['full_name'];
            $postUrl = BASE_URL . '/index.php?url=manage-cleaners';
        ?>
          <tr>
            <td><b><?= e($c['full_name']) ?></b><span class="m">@<?= e($c['username']) ?></span></td>
            <td><?= e($c['email']) ?><br><span class="m"><?= e($c['phone']) ?></span></td>
            <td><?= e($c['address']) ?></td>
            <td><?= (int)$c['jobs_total'] ?></td>
            <td><span class="pill<?= $blocked ? ' warn' : '' ?>"><?= $blocked ? 'Banned' : 'Active' ?></span></td>
            <td>
              <?= Ui::rowMenu('menu_k' . $cid, 'More actions for ' . $name, [
                  $blocked
                    ? ['label' => 'Unban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'unban', 'cleaner_id' => $cid],
                       'title' => 'Unban ' . $name . '?', 'text' => $name . ' will be able to log in again and can be assigned to jobs.', 'ok' => 'Unban cleaner']
                    : ['label' => 'Ban', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'ban', 'cleaner_id' => $cid],
                       'title' => 'Ban ' . $name . '?', 'text' => $name . ' will be signed out, will not be able to log in, and will no longer be offered for new jobs.', 'ok' => 'Ban cleaner', 'tone' => 'danger'],
                  ['label' => 'Delete', 'url' => $postUrl, 'csrf' => $csrf, 'fields' => ['action' => 'delete', 'cleaner_id' => $cid],
                   'title' => 'Delete ' . $name . '?', 'text' => 'This permanently deletes ' . $name . ' from the database. Their completed jobs stay on record without a cleaner. This cannot be undone.', 'ok' => 'Delete cleaner', 'tone' => 'danger', 'red' => true],
              ]) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="empty">No cleaners yet. Use "Add a cleaner" to create the first account.</p>
  <?php endif; ?>
</section>

<!-- Add a cleaner (pop-up) -->
<dialog class="dlg" id="addCleanerDialog" aria-labelledby="addCleanerTitle"<?= array_diff_key($errors, ['page' => 1]) ? ' data-open' : '' ?>>
  <div class="dh">
    <h2 id="addCleanerTitle">Add a cleaner</h2>
    <button class="x" type="button" data-close-dialog aria-label="Close">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B1B2B" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>Close
    </button>
  </div>
  <form method="post" action="<?= BASE_URL ?>/index.php?url=manage-cleaners" data-validate data-mode="cleaner" novalidate>
    <div class="db">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

      <div class="two">
        <div>
          <label for="full_name">Full name</label>
          <input class="in" id="full_name" name="full_name" autocomplete="off" value="<?= e($old['full_name']) ?>">
          <p class="err" id="full_name-err" aria-live="polite"><?= $err('full_name') ?></p>
        </div>
        <div>
          <label for="username">Username</label>
          <input class="in" id="username" name="username" autocomplete="off" value="<?= e($old['username']) ?>">
          <p class="err" id="username-err" aria-live="polite"><?= $err('username') ?></p>
        </div>
        <div>
          <label for="email">Email</label>
          <input class="in" id="email" name="email" type="email" autocomplete="off" value="<?= e($old['email']) ?>">
          <p class="err" id="email-err" aria-live="polite"><?= $err('email') ?></p>
        </div>
        <div>
          <label for="phone">Phone</label>
          <input class="in" id="phone" name="phone" type="tel" autocomplete="off" value="<?= e($old['phone']) ?>">
          <p class="err" id="phone-err" aria-live="polite"><?= $err('phone') ?></p>
        </div>
      </div>

      <label for="address">Address</label>
      <input class="in" id="address" name="address" autocomplete="off" value="<?= e($old['address']) ?>">
      <p class="err" id="address-err" aria-live="polite"><?= $err('address') ?></p>

      <p class="hint">The account starts with the password <b><?= e(DEFAULT_CLEANER_PASSWORD) ?></b>. The cleaner can change it in Settings after logging in.</p>
    </div>
    <div class="df"><button class="btn" type="submit" style="width:100%">Create cleaner account</button></div>
  </form>
</dialog>

<script src="<?= BASE_URL ?>/assets/js/dialog.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
