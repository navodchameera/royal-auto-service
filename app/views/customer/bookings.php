<?php
defined('APP_PATH') or exit;
$title       = 'My bookings';
$navActive   = 'bookings';
$base        = BASE_URL . '/index.php?url=';
$post        = $base . 'bookings&filter=' . $filter;
$cancellable = ['requested', 'quoted', 'accepted', 'assigned'];
require APP_PATH . '/views/layout/app_header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/review.css">
<?php

// What the customer sees as the status
$labels = [
    'requested'   => 'Waiting for quotation',
    'quoted'      => 'Quotation ready',
    'accepted'    => 'Confirmed',
    'assigned'    => 'Cleaner assigned',
    'in_progress' => 'In progress',
    'completed'   => 'Job done · please review',
    'finished'    => 'Completed',
    'cancelled'   => 'Cancelled',
];
$stars = function (int $n) { return str_repeat('★', $n) . str_repeat('☆', 5 - $n); };
?>
<div class="top">
  <div>
    <h1>My bookings</h1>
    <div class="sub">We confirm each booking with a price and a cleaner. You can cancel any time before the work starts.</div>
  </div>
  <a class="btn" href="<?= e($base) ?>book">Book a service</a>
</div>

<?php if (!empty($flash)): ?><div class="alert ok" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>

<nav class="f" aria-label="Filter bookings">
  <?php foreach ($filters as $key => $label): ?>
    <a href="<?= e($base) ?>bookings&amp;filter=<?= e($key) ?>"<?= $key === $filter ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$bookings): ?>
  <section class="card"><p class="empty">No bookings here yet.</p></section>
<?php endif; ?>

<?php foreach ($bookings as $b):
    $id        = (int)$b['booking_id'];
    $st        = $b['status'];
    $canCancel = in_array($st, $cancellable, true);
    $rejected  = $st === 'cancelled' && $b['q_status'] === 'rejected';
    $hasQuote  = $b['quoted_price'] !== null && $st !== 'cancelled';
    $upcoming  = in_array($st, ['requested', 'quoted', 'accepted', 'assigned'], true);
    $startTs   = date('c', strtotime($b['appointment_date'] . ' ' . $b['appointment_time']));
    $reviewed  = $b['review_rating'] !== null;
    $isForm    = $reviewId === $id;                       // this card's review form had a problem
    $old       = $isForm ? $reviewOld : ['rating' => '', 'comment' => ''];
    $errs      = $isForm ? $reviewErrors : [];
?>
  <section class="card" id="booking-<?= $id ?>">
    <div class="bk">
      <div class="g1">
        <h2 style="margin:0 0 4px">#<?= $id ?> · <?= e($b['make_model']) ?> <span class="m" style="font-weight:400"><?= e($b['plate_no']) ?></span></h2>
        <div class="m"><?= e(date('D j M Y, H:i', strtotime($b['appointment_date'] . ' ' . $b['appointment_time']))) ?> · <?= e($b['package_name']) ?></div>
        <div class="m"><?= e(location_label($b['location_type'])) ?><?= $b['job_address'] ? ' · ' . e($b['job_address']) : '' ?> · <?= (int)$b['photo_count'] ?> photo<?= (int)$b['photo_count'] === 1 ? '' : 's' ?></div>
        <p style="margin:12px 0 0"><span class="pill<?= $st === 'completed' ? ' ink' : '' ?><?= $rejected ? ' warn' : '' ?>"><?= $rejected ? 'Rejected' : e($labels[$st] ?? status_label($st)) ?></span></p>
        <?php if ($upcoming): ?>
          <p class="m" style="margin:10px 0 0">Starts in <b class="cd" data-countdown="<?= e($startTs) ?>"></b></p>
        <?php endif; ?>

        <?php if ($canCancel): ?>
          <div class="acts" style="margin-top:14px">
            <?= Ui::postButton($post, $csrf, ['action' => 'cancel', 'booking_id' => $id], 'Cancel booking', 'g',
                  ['Cancel booking #' . $id . '?', 'Your booking will be cancelled and the cleaner will be released for that time. You can book again whenever you like.', 'Cancel booking']) ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($hasQuote): ?>
        <div class="qbox">
          <div class="m">Quotation</div>
          <div class="qprice"><?= e(money($b['quoted_price'])) ?></div>
          <?php if ($b['condition_level']): ?><p style="margin:0 0 6px"><span class="pill">Condition: <?= e(ucfirst($b['condition_level'])) ?></span></p><?php endif; ?>
          <?php if ($b['remarks']): ?><p class="m" style="margin:0 0 6px"><?= nl2br(e($b['remarks'])) ?></p><?php endif; ?>
          <?php if ($st === 'assigned'): ?>
            <p class="m" style="margin:0">Your booking is confirmed at this price. If you do not want to go ahead, cancel it before the work starts.</p>
          <?php endif; ?>

          <?php if ($b['pay_method']): ?>
            <p style="margin:10px 0 0"><span class="pill">Paid · <?= e(method_label($b['pay_method'])) ?></span></p>
          <?php elseif (in_array($st, ['assigned', 'in_progress'], true)): ?>
            <div class="acts" style="margin-top:12px"><a class="btn" href="<?= e($base) ?>pay&amp;id=<?= $id ?>">Pay online</a></div>
            <p class="m" style="margin:8px 0 0">Or pay the cleaner in cash. The job is completed after payment.</p>
          <?php endif; ?>
        </div>
      <?php elseif ($rejected): ?>
        <div class="qbox">
          <div class="m">Why this booking was rejected</div>
          <p style="margin:6px 0 0"><?= nl2br(e($b['remarks'])) ?></p>
        </div>
      <?php elseif ($st === 'requested'): ?>
        <div class="qbox"><div class="m">We are reviewing your photos and will confirm your booking with a price and a cleaner.</div></div>
      <?php endif; ?>
    </div>

    <?php if ($st === 'completed'): ?>
      <div class="rvbox">
        <?php if (!$reviewed): ?>
          <h2>The cleaner has completed this job</h2>
          <p class="m" style="margin-top:0">Please rate the service and write a short review. Then you can complete your booking.</p>
          <form method="post" action="<?= e($post) ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="review">
            <input type="hidden" name="booking_id" value="<?= $id ?>">

            <fieldset class="fs">
              <legend>Your rating</legend>
              <div class="radios">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <label><input type="radio" name="rating" value="<?= $i ?>" required<?= $old['rating'] === (string)$i ? ' checked' : '' ?>><?= $i ?> ★</label>
                <?php endfor; ?>
              </div>
              <p class="err"><?= e($errs['rating'] ?? '') ?></p>
            </fieldset>

            <label for="comment-<?= $id ?>">Your review</label>
            <textarea class="in" id="comment-<?= $id ?>" name="comment" rows="3" minlength="3" maxlength="1000" required><?= e($old['comment']) ?></textarea>
            <p class="err"><?= e($errs['comment'] ?? '') ?></p>

            <button class="btn" type="submit">Submit review</button>
          </form>
        <?php else: ?>
          <h2>Thank you for your review</h2>
          <p style="margin:0 0 4px"><span class="st" role="img" aria-label="<?= (int)$b['review_rating'] ?> out of 5 stars"><?= e($stars((int)$b['review_rating'])) ?></span></p>
          <p class="m" style="margin:0 0 14px"><?= nl2br(e($b['review_comment'])) ?></p>
          <?= Ui::postButton($post, $csrf, ['action' => 'finish', 'booking_id' => $id], 'Complete booking', '',
                ['Complete booking #' . $id . '?', 'This marks your booking as fully completed.', 'Complete booking']) ?>
        <?php endif; ?>
      </div>
    <?php elseif ($st === 'finished' && $reviewed): ?>
      <div class="rvbox">
        <div class="acts" style="margin-bottom:12px"><a class="btn g" href="<?= e($base) ?>bill&amp;id=<?= $id ?>">View bill</a><a class="btn g" href="<?= e($base) ?>bill&amp;id=<?= $id ?>&amp;download=1">Download PDF</a></div>
        <p style="margin:0 0 4px"><span class="st" role="img" aria-label="<?= (int)$b['review_rating'] ?> out of 5 stars"><?= e($stars((int)$b['review_rating'])) ?></span> <span class="m">Your review</span></p>
        <p class="m" style="margin:0"><?= nl2br(e($b['review_comment'])) ?></p>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<script src="<?= BASE_URL ?>/assets/js/countdown.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
