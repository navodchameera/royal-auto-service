<?php
defined('APP_PATH') or exit;
$terms = [
  ['Using the service', 'Royal Auto Service provides vehicle washing and detailing at our garage and at the customer’s location. By creating an account you agree to these terms.'],
  ['Your account', 'Give accurate details and keep your password private. You are responsible for activity on your account. We may suspend accounts that are misused.'],
  ['Bookings', 'A booking is confirmed only after the quotation is accepted and a cleaner is assigned. Arrive on time for garage visits and be available at the agreed time for customer site visits.'],
  ['Vehicle photos', 'Upload clear photos of your own vehicle only. We use them to judge its condition, prepare a quotation and keep a record of its state before the service. Photos stay private to you and our staff.'],
  ['Quotations and pricing', 'The price is a base price for your vehicle type and package, adjusted for condition and any additional services. If the vehicle is in worse condition than the photos showed, a price change must be approved by you before work starts.'],
  ['Customer site services', 'At your location you provide water, electricity and a safe space to work where the service needs them. If these are not available, we may move or cancel the job.'],
  ['Payments', 'Payment is due for the final agreed price once the job is completed. Payment details are recorded by our staff and shown in your account.'],
  ['Cancellations', 'You can cancel or change a booking up to [CANCELLATION WINDOW] before the appointment. Later changes may not be accepted.'],
  ['Ratings and reviews', 'You may rate and review a completed job. Reviews must be honest and respectful, and we may remove content that is abusive or unrelated to the service.'],
  ['Care and liability', 'We take reasonable care of your vehicle. Tell us about existing damage before the service. Remove valuables from the vehicle. Our liability is limited as the law allows: [LIABILITY CLAUSE].'],
  ['Your data', 'We collect your name, contact details, address, vehicle details and photos to provide the service. We do not sell your data. Contact us to correct or delete it.'],
  ['Changes and contact', 'We may update these terms and will show the latest version here. Questions: [CONTACT EMAIL OR PHONE].'],
];
?>
<dialog class="dlg" id="termsDialog" aria-labelledby="termsTitle">
  <div class="dh">
    <h2 id="termsTitle">Terms and Conditions</h2>
    <button class="x" type="button" data-close-terms aria-label="Close terms and conditions">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B1B2B" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19"/></svg>Close
    </button>
  </div>
  <div class="db">
    <?php foreach ($terms as $i => [$heading, $text]): ?>
      <h3><?= $i + 1 ?>. <?= e($heading) ?></h3>
      <p><?= e($text) ?></p>
    <?php endforeach; ?>
  </div>
  <div class="df">
    <button class="btn" type="button" data-agree>I agree</button>
  </div>
</dialog>
