// Ask before deleting a vehicle.
(function () {
  'use strict';
  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirm]'), function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
    });
  });
})();
