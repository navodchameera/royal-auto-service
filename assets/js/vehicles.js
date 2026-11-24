// My vehicles: "Add a vehicle" pop-up and expandable Edit panels.
(function () {
  'use strict';

  // ---- Add dialog
  var dialog = document.getElementById('addVehicleDialog');
  if (dialog) {
    var openBtn = document.querySelector('[data-open-add]');
    if (openBtn) { openBtn.addEventListener('click', function () { dialog.showModal(); }); }

    Array.prototype.forEach.call(dialog.querySelectorAll('[data-close-dialog]'), function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    // A click on the dark backdrop closes it too
    dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });

    // Re-open after a failed save, or when linked with &add=1
    if (dialog.hasAttribute('data-open')) { dialog.showModal(); }
  }

  // ---- Edit panels (one open at a time)
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-edit]'));

  function setOpen(btn, open) {
    var panel = document.getElementById(btn.getAttribute('data-edit'));
    if (!panel) { return; }
    panel.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.textContent = open ? 'Close' : 'Edit';
    if (open) {
      var first = panel.querySelector('.in');
      if (first) { first.focus(); }
    }
  }

  buttons.forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute('data-edit'));
    if (panel && !panel.hidden) { btn.textContent = 'Close'; }   // opened by the server after an error

    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') !== 'true';
      buttons.forEach(function (other) { if (other !== btn) { setOpen(other, false); } });
      setOpen(btn, open);
    });
  });

  Array.prototype.forEach.call(document.querySelectorAll('[data-cancel-edit]'), function (cancel) {
    cancel.addEventListener('click', function () {
      var panel = cancel.closest('.vedit');
      var btn = document.querySelector('[data-edit="' + panel.id + '"]');
      panel.reset();                       // forget unsaved changes
      if (btn) { setOpen(btn, false); btn.focus(); }
    });
  });
})();
