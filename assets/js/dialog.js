// Generic pop-up dialogs: <button data-open-dialog="id"> opens <dialog class="dlg" id="id">.
(function () {
  'use strict';

  Array.prototype.forEach.call(document.querySelectorAll('[data-open-dialog]'), function (btn) {
    btn.addEventListener('click', function () {
      var dialog = document.getElementById(btn.getAttribute('data-open-dialog'));
      if (dialog) { dialog.showModal(); }
    });
  });

  Array.prototype.forEach.call(document.querySelectorAll('dialog.dlg'), function (dialog) {
    Array.prototype.forEach.call(dialog.querySelectorAll('[data-close-dialog]'), function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    // A click on the dark backdrop closes it too
    dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });
    // Re-open after a failed save
    if (dialog.hasAttribute('data-open')) { dialog.showModal(); }
  });
})();
