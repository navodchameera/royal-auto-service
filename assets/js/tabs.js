// Sliding pill for the Log in / Register tabs.
// Plain JavaScript version of the "continuous tabs" animation:
// the dark pill glides from the old tab to the new one with a springy ease.
(function () {
  'use strict';

  var nav = document.querySelector('[data-tabs]');
  if (!nav) { return; }

  var pill   = nav.querySelector('.tab-pill');
  var tabs   = Array.prototype.slice.call(nav.querySelectorAll('.tab'));
  var active = nav.querySelector('.tab.on') || tabs[0];
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function place(tab, animate) {
    pill.style.transition = animate ? '' : 'none';
    pill.style.width      = tab.offsetWidth + 'px';
    pill.style.transform  = 'translateX(' + tab.offsetLeft + 'px)';
  }

  nav.classList.add('js');

  // The two tabs are separate pages, so remember which tab we came from
  // and start the pill there, then slide it to the current tab.
  var fromId = null;
  try {
    fromId = sessionStorage.getItem('authTabFrom');
    sessionStorage.removeItem('authTabFrom');
  } catch (e) { /* storage blocked: skip the slide */ }

  var from = tabs.filter(function (t) { return t.dataset.tab === fromId; })[0];
  var slide = from && from !== active && !reduce;

  place(slide ? from : active, false);
  pill.style.opacity = '1';

  if (slide) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { place(active, true); });
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      try { sessionStorage.setItem('authTabFrom', active.dataset.tab); } catch (e) { /* ignore */ }
    });
  });

  window.addEventListener('resize', function () { place(active, false); });
})();
