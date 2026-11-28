// Live countdown. <b data-countdown="2026-12-01T09:30:00+05:30"></b> shows "2d 4h 12m 05s".
// The target carries its own time zone offset, so it is correct on any device.
(function () {
  'use strict';

  var nodes = document.querySelectorAll('[data-countdown]');
  if (!nodes.length) { return; }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function text(ms) {
    if (ms <= 0) { return 'a moment'; }
    var s = Math.floor(ms / 1000);
    var d = Math.floor(s / 86400); s -= d * 86400;
    var h = Math.floor(s / 3600);  s -= h * 3600;
    var m = Math.floor(s / 60);    s -= m * 60;
    if (d > 0) { return d + 'd ' + h + 'h ' + m + 'm ' + pad(s) + 's'; }
    if (h > 0) { return h + 'h ' + pad(m) + 'm ' + pad(s) + 's'; }
    return m + 'm ' + pad(s) + 's';
  }

  function tick() {
    var now = Date.now();
    nodes.forEach(function (el) {
      var target = Date.parse(el.getAttribute('data-countdown'));
      if (isNaN(target)) { el.textContent = ''; return; }
      el.textContent = text(target - now);
    });
  }

  tick();
  setInterval(tick, 1000);
})();
