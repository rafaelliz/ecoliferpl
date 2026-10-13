/* Dropdown kustom (tampilan konsisten) di atas <select> asli; mendukung keyboard & ARIA. */
(function () {
  'use strict';
  var Eco = window.Eco;
  var ARROW = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

  function closeAll(host, except) {
    host.querySelectorAll('.select-ui.is-open').forEach(function (b) {
      if (b === except) return;
      b.classList.remove('is-open'); b.querySelector('.select-trigger').setAttribute('aria-expanded', 'false');
    });
  }

  function enhance(host) {
    host.querySelectorAll('select[data-k]').forEach(function (select) {
      if (select.dataset.enhanced) return;
      select.dataset.enhanced = 'true';
      select.classList.add('select-native'); select.tabIndex = -1; select.setAttribute('aria-hidden', 'true');

      var box = document.createElement('div'); box.className = 'select-ui';
      select.parentNode.insertBefore(box, select); box.appendChild(select);

      var trigger = document.createElement('button'); trigger.type = 'button'; trigger.className = 'select-trigger';
      trigger.setAttribute('aria-haspopup', 'listbox'); trigger.setAttribute('aria-expanded', 'false');
      var lbl = document.getElementById(select.id + '-l'); if (lbl) trigger.setAttribute('aria-labelledby', lbl.id + ' ' + select.id + '-v');
      var value = document.createElement('span'); value.id = select.id + '-v';
      var arrow = document.createElement('span'); arrow.innerHTML = ARROW; arrow.style.display = 'grid';
      trigger.appendChild(value); trigger.appendChild(arrow.firstChild); box.appendChild(trigger);
      if (lbl) lbl.addEventListener('click', function (e) { e.preventDefault(); trigger.focus(); });

      var menu = document.createElement('div'); menu.className = 'select-menu'; menu.setAttribute('role', 'listbox');
      menu.id = 'menu-' + select.id; trigger.setAttribute('aria-controls', menu.id); box.appendChild(menu);

      var items = [];
      Array.prototype.forEach.call(select.options, function (opt) {
        var it = document.createElement('button'); it.type = 'button'; it.className = 'select-option';
        it.setAttribute('role', 'option'); it.textContent = opt.textContent; it.dataset.value = opt.value; it.tabIndex = -1;
        menu.appendChild(it); items.push(it);
        it.addEventListener('click', function () {
          select.value = opt.value; sync(); close();
          select.dispatchEvent(new Event('change', { bubbles: true }));
          trigger.classList.remove('value-changed'); void trigger.offsetWidth; trigger.classList.add('value-changed');
          setTimeout(function () { trigger.classList.remove('value-changed'); }, 420);
          trigger.focus();
        });
      });

      function sync() {
        var opt = select.options[select.selectedIndex];
        value.textContent = opt ? opt.textContent : 'Pilih';
        items.forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.value === select.value)); });
      }
      function close() { box.classList.remove('is-open'); trigger.setAttribute('aria-expanded', 'false'); }
      function open() { closeAll(host, box); box.classList.add('is-open'); trigger.setAttribute('aria-expanded', 'true'); sync(); }
      function focusSel() { var i = Math.max(0, items.findIndex(function (b) { return b.dataset.value === select.value; })); if (items[i]) items[i].focus(); }

      trigger.addEventListener('click', function () { box.classList.contains('is-open') ? close() : open(); });
      trigger.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); setTimeout(focusSel, 30); }
      });
      menu.addEventListener('keydown', function (e) {
        var i = items.indexOf(document.activeElement);
        if (e.key === 'Escape') { e.preventDefault(); close(); trigger.focus(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); if (items[Math.min(items.length - 1, i + 1)]) items[Math.min(items.length - 1, i + 1)].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (items[Math.max(0, i - 1)]) items[Math.max(0, i - 1)].focus(); }
        else if (e.key === 'Tab') close();
      });
      select.addEventListener('change', sync); sync();
    });
  }

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.select-ui')) closeAll(document);
  });
  Eco.enhanceSelects = enhance;
})();
