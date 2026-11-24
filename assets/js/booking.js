// Book a service: form behaviour, live summary, photo previews and checks.
// (The PHP controller validates everything again on the server.)
(function () {
  'use strict';

  var form = document.getElementById('bookingForm');
  if (!form) { return; }

  var MAX = 5 * 1024 * 1024;
  var OK = ['image/jpeg', 'image/png', 'image/webp'];
  var vehicle = form.querySelector('#vehicle_id');
  var newBox = form.querySelector('#newVehicle');
  var addrBox = form.querySelector('#addressBlock');
  var box = document.getElementById('clientErrors');

  function q(sel) { return form.querySelector(sel); }
  function checked(name) { return form.querySelector('input[name="' + name + '"]:checked'); }
  function put(id, value) { document.getElementById(id).textContent = value; }

  function show(messages) {
    box.textContent = messages.join(' ');
    box.hidden = false;
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function refresh() {
    newBox.hidden = vehicle.value !== 'new';
    var loc = checked('location_type');
    addrBox.hidden = !loc || loc.value !== 'customer_site';

    var vehicleText = vehicle.value === '' ? '–'
      : vehicle.value === 'new' ? (q('#nv_make_model').value.trim() || 'New vehicle')
      : vehicle.options[vehicle.selectedIndex].text;
    var pkg = checked('package_id');
    var d = q('#appointment_date').value, t = q('#appointment_time').value;

    put('sumVehicle', vehicleText);
    put('sumPackage', pkg ? pkg.dataset.name : '–');
    put('sumRange', pkg ? pkg.dataset.range : '–');
    put('sumWhen', d ? d + (t ? ', ' + t : '') : '–');
    put('sumWhere', loc ? loc.dataset.label : '–');
  }

  form.addEventListener('input', refresh);
  form.addEventListener('change', refresh);
  refresh();

  // Photo slots: preview and basic checks
  var slots = form.querySelectorAll('.slot input[type="file"]');
  Array.prototype.forEach.call(slots, function (input) {
    input.addEventListener('change', function () {
      var slot = input.closest('.slot');
      var img = slot.querySelector('img');
      var file = input.files[0];

      slot.classList.remove('has', 'bad');
      img.hidden = true;
      if (!file) { return; }

      if (OK.indexOf(file.type) === -1 || file.size > MAX) {
        input.value = '';
        slot.classList.add('bad');
        show([slot.dataset.label + ': use a JPG, PNG or WebP image under 5 MB.']);
        return;
      }
      box.hidden = true;
      img.src = URL.createObjectURL(file);
      img.hidden = false;
      slot.classList.add('has');
    });
  });

  form.addEventListener('submit', function (event) {
    var m = [];
    var loc = checked('location_type');

    if (vehicle.value === '') { m.push('Select a vehicle.'); }
    if (vehicle.value === 'new') {
      if (q('#nv_type').value === '') { m.push('Select a vehicle type.'); }
      if (q('#nv_make_model').value.trim().length < 2) { m.push('Enter the make and model.'); }
      if (!/^[A-Za-z0-9 -]{3,15}$/.test(q('#nv_plate').value.trim())) { m.push('Enter a valid plate number.'); }
    }
    if (!checked('package_id')) { m.push('Choose a package.'); }
    if (!loc) {
      m.push('Choose where we should wash it.');
    } else if (loc.value === 'customer_site' && q('#job_address').value.trim() === '') {
      m.push('Enter the address for this booking.');
    }
    if (!q('#appointment_date').value) { m.push('Choose a date.'); }
    if (!q('#appointment_time').value) { m.push('Choose a time.'); }

    var photos = 0;
    Array.prototype.forEach.call(slots, function (input) { if (input.files.length) { photos++; } });
    if (loc && loc.value === 'customer_site' && photos === 0) {
      m.push('Add at least one photo so we can quote the price before we visit.');
    }

    if (m.length) {
      event.preventDefault();
      show(m);
    }
  });
})();
