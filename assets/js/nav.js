// Collapsible side menu on phones: icons only when collapsed, tap the menu button to expand.
(function () {
  'use strict';

  var app = document.getElementById('app');
  var btn = document.getElementById('menuBtn');
  var scrim = document.getElementById('scrim');
  if (!app || !btn || !scrim) { return; }

  function set(open) {
    app.classList.toggle('open', open);
    scrim.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  }

  btn.addEventListener('click', function () { set(!app.classList.contains('open')); });
  scrim.addEventListener('click', function () { set(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && app.classList.contains('open')) { set(false); btn.focus(); }
  });

  // Going back to a wide screen: make sure the menu is not left open
  var wide = window.matchMedia('(min-width: 861px)');
  var onChange = function (e) { if (e.matches) { set(false); } };
  if (wide.addEventListener) { wide.addEventListener('change', onChange); } else { wide.addListener(onChange); }
})();
