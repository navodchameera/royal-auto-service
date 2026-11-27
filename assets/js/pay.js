// Demo payment form: tidy up what is typed and show "Processing..." after a valid submit.
(function () {
  'use strict';

  var form = document.getElementById('payForm');
  if (!form) { return; }

  var number = form.querySelector('#card_number');
  var expiry = form.querySelector('#card_expiry');
  var cvv = form.querySelector('#card_cvv');
  var button = document.getElementById('payBtn');

  // 1234 5678 9012 3456
  number.addEventListener('input', function () {
    var digits = number.value.replace(/\D/g, '').slice(0, 19);
    number.value = digits.replace(/(.{4})/g, '$1 ').trim();
  });

  // MM/YY
  expiry.addEventListener('input', function () {
    var digits = expiry.value.replace(/\D/g, '').slice(0, 4);
    expiry.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
  });

  cvv.addEventListener('input', function () { cvv.value = cvv.value.replace(/\D/g, '').slice(0, 4); });

  // The checking script may stop the submit, so look again once every listener has run
  form.addEventListener('submit', function (event) {
    setTimeout(function () {
      if (!event.defaultPrevented) {
        button.disabled = true;
        button.textContent = 'Processing…';
      }
    }, 0);
  });
})();
