<?php
defined('APP_PATH') or exit;
$title     = 'Pay online';
$navActive = 'payments';
$base      = BASE_URL . '/index.php?url=';
$id        = (int)$b['booking_id'];
require APP_PATH . '/views/layout/app_header.php';
$err = function ($key) use ($errors) { return e($errors[$key] ?? ''); };
?>
<div class="top">
  <div>
    <a class="back" href="<?= e($base) ?>payments">← Payments</a>
    <h1>Pay online</h1>
    <div class="sub">Booking #<?= $id ?> · <?= e($b['make_model']) ?> · <?= e($b['package_name']) ?></div>
  </div>
</div>

<?php if (!empty($errors['form'])): ?><div class="alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="g2" style="grid-template-columns:minmax(0,520px);">
  <section class="card">
    <div class="alert note">
      <b>Demo payment page.</b> No real payment is made and your card details are not saved.
      Try the test card <b>4242 4242 4242 4242</b> with any future date and any 3 digit code.
    </div>

    <div class="paytotal"><span class="m">Amount to pay</span><span class="big" style="margin:0"><?= e(money($b['quoted_price'])) ?></span></div>

    <form id="payForm" method="post" action="<?= e($base) ?>pay&amp;id=<?= $id ?>" data-validate data-mode="payment" novalidate autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="booking_id" value="<?= $id ?>">

      <label for="card_name">Name on card</label>
      <input class="in" id="card_name" name="card_name" autocomplete="off" value="<?= e($old['card_name']) ?>">
      <p class="err" id="card_name-err" aria-live="polite"><?= $err('card_name') ?></p>

      <label for="card_number">Card number</label>
      <input class="in" id="card_number" name="card_number" inputmode="numeric" autocomplete="off" placeholder="1234 5678 9012 3456" maxlength="23">
      <p class="err" id="card_number-err" aria-live="polite"><?= $err('card_number') ?></p>

      <div class="two">
        <div>
          <label for="card_expiry">Expiry (MM/YY)</label>
          <input class="in" id="card_expiry" name="card_expiry" inputmode="numeric" autocomplete="off" placeholder="MM/YY" maxlength="5" value="<?= e($old['card_expiry']) ?>">
          <p class="err" id="card_expiry-err" aria-live="polite"><?= $err('card_expiry') ?></p>
        </div>
        <div>
          <label for="card_cvv">Security code</label>
          <input class="in" id="card_cvv" name="card_cvv" type="password" inputmode="numeric" autocomplete="off" placeholder="123" maxlength="4">
          <p class="err" id="card_cvv-err" aria-live="polite"><?= $err('card_cvv') ?></p>
        </div>
      </div>

      <button class="btn" type="submit" id="payBtn" style="width:100%">Pay <?= e(money($b['quoted_price'])) ?></button>
    </form>
  </section>
</div>
<script src="<?= BASE_URL ?>/assets/js/pay.js" defer></script>
<?php require APP_PATH . '/views/layout/app_footer.php'; ?>
