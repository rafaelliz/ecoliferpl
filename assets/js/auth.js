/* Login/registrasi: transisi geser antar mode, tampil/sembunyi password, validasi konfirmasi password. */
(function () {
  'use strict';
  var card = document.getElementById('authCard'); if (!card) return;
  var R = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var panes = { login: document.getElementById('paneLogin'), register: document.getElementById('paneRegister') };
  var titles = { login: 'Masuk · Ecolife', register: 'Daftar · Ecolife' };

  document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var inp = btn.parentNode.querySelector('input'), show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', String(show));
      btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });

  function setMode(mode, focus) {
    card.setAttribute('data-mode', mode);
    Object.keys(panes).forEach(function (k) {
      var on = k === mode;
      panes[k].inert = !on;
      if (on) panes[k].removeAttribute('aria-hidden'); else panes[k].setAttribute('aria-hidden', 'true');
    });
    document.title = titles[mode];
    var p = panes[mode];
    p.classList.remove('is-entering'); void p.offsetWidth; if (!R) p.classList.add('is-entering');
    if (focus) {
      setTimeout(function () { var f = p.querySelector('input:not([type=hidden])'); if (f) f.focus({ preventScroll: true }); }, R ? 0 : 450);
    }
  }

  document.querySelectorAll('[data-switch]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
      e.preventDefault();
      var mode = a.getAttribute('data-switch');
      if (card.getAttribute('data-mode') === mode) return;
      setMode(mode, true);
      try { history.pushState({ mode: mode }, '', a.getAttribute('href')); } catch (err) {}
    });
  });
  window.addEventListener('popstate', function () {
    setMode(/register/.test(location.pathname) ? 'register' : 'login', false);
  });

  var reg = document.querySelector('form[data-register]');
  if (reg) {
    var p1 = reg.querySelector('[name=password]'), p2 = reg.querySelector('[name=password2]');
    var check = function () { p2.setCustomValidity(p2.value && p1.value !== p2.value ? 'Konfirmasi password tidak sama.' : ''); };
    p1.addEventListener('input', check); p2.addEventListener('input', check);
  }
})();
