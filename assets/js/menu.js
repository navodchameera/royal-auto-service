// "Three dots" row menus: <button data-menu aria-controls="id"> opens <div id="id" class="menu-pop">.
(function () {
  'use strict';

  var open = null;   // { btn, menu }

  function close() {
    if (!open) { return; }
    open.menu.hidden = true;
    open.btn.setAttribute('aria-expanded', 'false');
    open = null;
  }

  function place(btn, menu) {
    var rect = btn.getBoundingClientRect();
    var width = menu.offsetWidth;
    var top = rect.bottom + 6;
    if (top + menu.offsetHeight > window.innerHeight - 8) { top = Math.max(8, rect.top - menu.offsetHeight - 6); }
    menu.style.top = top + 'px';
    menu.style.left = Math.max(8, rect.right - width) + 'px';
  }

  function show(btn) {
    var menu = document.getElementById(btn.getAttribute('aria-controls'));
    if (!menu) { return; }
    if (open && open.btn === btn) { close(); return; }
    close();
    menu.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    place(btn, menu);
    open = { btn: btn, menu: menu };
    var first = menu.querySelector('.menu-item');
    if (first) { first.focus(); }
  }

  document.addEventListener('click', function (event) {
    var btn = event.target.closest ? event.target.closest('[data-menu]') : null;
    if (btn) { event.preventDefault(); show(btn); return; }
    if (open && !open.menu.contains(event.target)) { close(); }
  });

  document.addEventListener('keydown', function (event) {
    if (!open) { return; }
    if (event.key === 'Escape') { var btn = open.btn; close(); btn.focus(); return; }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      var items = Array.prototype.slice.call(open.menu.querySelectorAll('.menu-item'));
      var index = items.indexOf(document.activeElement);
      index = event.key === 'ArrowDown' ? (index + 1) % items.length : (index - 1 + items.length) % items.length;
      items[index].focus();
    }
  });

  window.addEventListener('resize', close);
  window.addEventListener('scroll', close, true);
})();
