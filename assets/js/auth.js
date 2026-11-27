// Client-side validation, password toggle and terms dialog.
// (The PHP controller validates again on the server.)
(function () {
  'use strict';

  var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var phoneRe = /^\+?\d{9,15}$/;
  var passRe  = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;

  // Each rule returns true when valid, or an error message string.
  var rules = {
    full_name: function (v) { return v.trim().length >= 2 || 'Enter your full name.'; },
    email:     function (v) { return emailRe.test(v.trim()) || 'Enter a valid email address.'; },
    phone:     function (v) { return phoneRe.test(v.replace(/[\s-]/g, '')) || 'Enter a valid phone number.'; },
    password:  function (v, f) {
      if (f.dataset.mode === 'login') { return v.length > 0 || 'Enter your password.'; }
      return passRe.test(v) || 'Use at least 8 characters with a letter and a number.';
    },
    login:     function (v) { return v.trim().length > 0 || 'Enter your username or email.'; },
    username:  function (v) { return /^[A-Za-z0-9_.]{3,30}$/.test(v) || 'Use 3 to 30 letters, numbers, dots or underscores.'; },
    current_password: function (v) { return v.length > 0 || 'Enter your current password.'; },
    new_password: function (v) { return passRe.test(v) || 'Use at least 8 characters with a letter and a number.'; },
    confirm_password: function (v, f) { return v === f.querySelector('#password, #new_password').value || 'Passwords do not match.'; },
    vehicle_type: function (v) { return v !== '' || 'Select a vehicle type.'; },
    make_model: function (v) { v = v.trim(); return (v.length >= 2 && v.length <= 100) || 'Enter the make and model.'; },
    plate_no:  function (v) { return /^[A-Za-z0-9 -]{3,15}$/.test(v.trim()) || 'Enter a valid plate number.'; },
    colour:    function (v) { return v.trim().length <= 30 || 'Colour is too long.'; },
    quoted_price: function (v) { var n = v.replace(/,/g, '').trim(); return (/^\d{1,7}(\.\d{1,2})?$/.test(n) && parseFloat(n) > 0) || 'Enter a price greater than 0, for example 6500.'; },
    cleaner_id: function (v) { return v !== '' || 'Select a cleaner.'; },
    card_name: function (v) { return /^[A-Za-z][A-Za-z .'-]{1,59}$/.test(v.trim()) || 'Enter the name on the card.'; },
    card_number: function (v) { return /^\d{13,19}$/.test(v.replace(/\s+/g, '')) || 'Enter a valid card number.'; },
    card_expiry: function (v) {
      var m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(v.trim());
      if (!m) { return 'Enter the expiry date as MM/YY.'; }
      var now = new Date(), year = 2000 + parseInt(m[2], 10), month = parseInt(m[1], 10);
      return (year > now.getFullYear() || (year === now.getFullYear() && month >= now.getMonth() + 1)) || 'This card has expired.';
    },
    card_cvv:  function (v) { return /^\d{3,4}$/.test(v) || 'Enter the 3 or 4 digit security code.'; },
    address:   function (v) {
      v = v.trim();
      if (v.length === 0) { return 'Enter your address.'; }
      return v.length <= 255 || 'Address is too long.';
    }
  };

  function showError(input, message) {
    var out = document.getElementById(input.id + '-err');
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    if (out) { out.textContent = message || ''; }
  }

  function check(input, mode) {
    var rule = rules[input.name];
    if (!rule) { return true; }
    var result = rule(input.value, input.form);
    showError(input, result === true ? '' : result);
    return result === true;
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var mode   = form.dataset.mode;
    var fields = Array.prototype.slice.call(form.querySelectorAll('.in'));

    fields.forEach(function (input) {
      input.addEventListener('blur', function () { check(input, mode); });
      input.addEventListener('input', function () {
        if (input.getAttribute('aria-invalid') === 'true') { check(input, mode); }
      });
    });

    // Re-check "confirm password" when the password changes
    var pw = form.querySelector('#password, #new_password'), cf = form.querySelector('#confirm_password');
    if (pw && cf) {
      pw.addEventListener('input', function () { if (cf.value !== '') { check(cf, mode); } });
    }

    form.addEventListener('submit', function (event) {
      var firstBad = null;
      var level = form.querySelectorAll('input[name="condition_level"]');
      if (level.length && !form.querySelector('input[name="condition_level"]:checked')) {
        var levelOut = document.getElementById('condition_level-err');
        if (levelOut) { levelOut.textContent = 'Select the vehicle condition.'; }
        firstBad = level[0];
      }
      fields.forEach(function (input) {
        if (!check(input, mode) && !firstBad) { firstBad = input; }
      });
      if (firstBad) {
        event.preventDefault();
        firstBad.focus();
      }
    });

    // Register page only: terms checkbox and dialog
    var terms  = form.querySelector('#terms');
    var submit = form.querySelector('#registerBtn');
    var dialog = document.getElementById('termsDialog');
    if (!terms || !submit) { return; }

    function syncSubmit() { submit.disabled = !terms.checked; }
    terms.addEventListener('change', syncSubmit);
    syncSubmit();

    if (!dialog) { return; }
    var opener = document.querySelector('[data-open-terms]');
    if (opener) { opener.addEventListener('click', function () { dialog.showModal(); }); }

    dialog.querySelectorAll('[data-close-terms]').forEach(function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    dialog.querySelector('[data-agree]').addEventListener('click', function () {
      terms.checked = true;
      syncSubmit();
      dialog.close();
    });
    // Click on the dark backdrop closes the dialog
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) { dialog.close(); }
    });
  });

  // Show / Hide password
  document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('aria-controls'));
      var show  = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
    });
  });
})();
