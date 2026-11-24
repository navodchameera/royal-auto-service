// Ask before a risky action (forms with data-confirm="question").
(function () {
  'use strict';
  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirm]'), function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
    });
  });
})();
