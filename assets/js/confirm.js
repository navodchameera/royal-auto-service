// Confirmation dialog. Any <form data-confirm="statement"> asks first.
// Optional: data-confirm-title, data-confirm-ok (button text), data-confirm-back (cancel text),
// data-confirm-tone="danger" (red confirm button).
(function () {
  'use strict';

  var dialog = document.getElementById('confirmDialog');
  if (!dialog) { return; }

  var title = document.getElementById('confirmTitle');
  var text = document.getElementById('confirmText');
  var ok = dialog.querySelector('[data-confirm-ok]');
  var back = dialog.querySelector('[data-confirm-back]');
  var pending = null;

  function ask(form) {
    pending = form;
    title.textContent = form.getAttribute('data-confirm-title') || 'Are you sure?';
    text.textContent = form.getAttribute('data-confirm');
    ok.textContent = form.getAttribute('data-confirm-ok') || 'Continue';
    back.textContent = form.getAttribute('data-confirm-back') || 'Cancel';
    ok.className = 'btn' + (form.getAttribute('data-confirm-tone') === 'danger' ? ' danger-solid' : '');
    dialog.showModal();
    back.focus();          // the safe choice is focused first
  }

  // Capture phase so we run before any other submit handler
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form && form.hasAttribute && form.hasAttribute('data-confirm') && !form.confirmed) {
      event.preventDefault();
      ask(form);
    }
  }, true);

  ok.addEventListener('click', function () {
    var form = pending;
    pending = null;
    dialog.close();
    if (form) {
      form.confirmed = true;
      form.submit();       // the action continues
    }
  });

  back.addEventListener('click', function () { pending = null; dialog.close(); });
  dialog.addEventListener('cancel', function () { pending = null; });        // Esc
  dialog.addEventListener('click', function (event) {                      // click on the dark backdrop
    if (event.target === dialog) { pending = null; dialog.close(); }
  });
})();
