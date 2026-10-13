/* Ecolife — interaksi umum: tema, navbar, menu pengguna, reveal, counter, parallax, toast, API helper. */
(function () {
  'use strict';
  var R = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ---------- Toast ---------- */
  var tt;
  function toast(msg, type) {
    var e = $('#toast'); if (!e) return;
    e.textContent = msg;
    e.className = 'on' + (type ? ' is-' + type : '');
    clearTimeout(tt);
    tt = setTimeout(function () { e.classList.remove('on'); }, 3800);
  }

  /* ---------- Helper ---------- */
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }
  function fmt(iso) {
    try { return new Date(iso).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }); }
    catch (e) { return ''; }
  }
  function csrf() { var m = $('meta[name="csrf-token"]'); return m ? m.content : ''; }
  function busy(btn, on) {
    if (!btn) return;
    btn.classList.toggle('is-loading', !!on);
    if (on) btn.setAttribute('aria-busy', 'true'); else btn.removeAttribute('aria-busy');
    btn.disabled = !!on;
  }
  function api(url, opt) {
    opt = opt || {};
    var init = { method: opt.method || 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (init.method !== 'GET') {
      init.headers['Content-Type'] = 'application/json';
      init.headers['X-CSRF-Token'] = csrf();
      init.body = JSON.stringify(opt.body || {});
    }
    return fetch(url, init).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Respons server tidak valid.' }; }).then(function (d) {
        d.status = r.status;
        if (r.status === 401) { window.location.href = 'login.php'; }
        return d;
      });
    }).catch(function () {
      return { ok: false, status: 0, error: 'Tidak dapat terhubung ke server. Periksa koneksi internetmu.' };
    });
  }
  function tween(el, from, to, ms) {
    if (!el) return;
    if (R || from === to) { el.textContent = to; return; }
    var t0 = performance.now();
    (function step(n) {
      var k = Math.min(1, (n - t0) / (ms || 700)), e = 1 - Math.pow(1 - k, 3);
      el.textContent = Math.round(from + (to - from) * e);
      if (k < 1) requestAnimationFrame(step); else el.textContent = to;
    })(t0);
  }
  window.Eco = { $: $, $$: $$, toast: toast, esc: esc, fmt: fmt, api: api, busy: busy, tween: tween, reduced: R };

  /* ---------- Tema ---------- */
  $$('#theme').forEach(function (b) {
    b.addEventListener('click', function () {
      var r = document.documentElement, next = r.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      r.setAttribute('data-theme', next);
      try { localStorage.setItem('ecolife_theme', next); } catch (e) {}
    });
  });

  /* ---------- Navbar ---------- */
  var nav = $('#nav'), prog = $('#prog'), up = $('#up'), tick = 0;
  function onScroll() {
    tick = 0;
    var y = window.scrollY, h = document.documentElement.scrollHeight - window.innerHeight;
    if (prog) prog.style.transform = 'scaleX(' + (h > 0 ? Math.min(1, y / h) : 0) + ')';
    if (nav) nav.classList.toggle('is-scrolled', y > 12);
    if (up) up.classList.toggle('on', y > 700);
  }
  window.addEventListener('scroll', function () { if (!tick) tick = requestAnimationFrame(onScroll); }, { passive: true });
  onScroll();
  if (up) up.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: R ? 'auto' : 'smooth' }); });

  var toggle = $('.nav__toggle');
  function closeNav() { if (nav) nav.classList.remove('is-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); }
  if (toggle) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      closeUser();
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    $$('.nav__links a').forEach(function (a) { a.addEventListener('click', closeNav); });
  }
  var um = $('.usermenu__btn'), panel = $('#usermenu');
  function closeUser() { if (um) { um.setAttribute('aria-expanded', 'false'); panel.hidden = true; } }
  if (um && panel) {
    um.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = panel.hidden; panel.hidden = !open; um.setAttribute('aria-expanded', String(open));
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    um.addEventListener('click', closeNav);
  }
  document.addEventListener('click', function () { closeUser(); closeNav(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeUser(); closeNav(); } });

  /* Indikator menu aktif saat scroll (landing) */
  var secs = $$('section[data-section]'), links = $$('.nav__links a[data-nav]');
  if (secs.length && 'IntersectionObserver' in window) {
    var setActive = function (id) {
      links.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('data-nav') === id); });
    };
    setActive('beranda');
    var no = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) setActive(e.target.id); });
    }, { rootMargin: '-45% 0px -50% 0px' });
    secs.forEach(function (s) { no.observe(s); });
  }

  /* ---------- Reveal saat scroll ---------- */
  var rv = $$('[data-reveal]');
  if (!('IntersectionObserver' in window) || R) {
    rv.forEach(function (e) { e.removeAttribute('data-reveal'); });
  } else {
    var ro = new IntersectionObserver(function (es) {
      es.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target; el.classList.add('is-in'); ro.unobserve(el);
        setTimeout(function () { el.removeAttribute('data-reveal'); el.classList.remove('is-in'); }, 1500);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    rv.forEach(function (e) { ro.observe(e); });
  }

  /* ---------- Counter angka ---------- */
  function parse(t) {
    var m = t.trim().match(/^(\D*?)(\d[\d.,]*)(.*)$/); if (!m) return null;
    var raw = m[2];
    return { pre: m[1], dec: (raw.split(',')[1] || '').length, val: parseFloat(raw.replace(/\./g, '').replace(',', '.')), suf: m[3] };
  }
  function nf(v, d) { return v.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }); }
  function count(el, delay) {
    var p = parse(el.textContent); if (!p || R) return;
    var final = el.textContent; el.setAttribute('aria-label', final);
    el.textContent = p.pre + nf(0, p.dec) + p.suf;
    setTimeout(function () {
      var t0 = performance.now();
      (function f(n) {
        var k = Math.min(1, (n - t0) / 1400), e = 1 - Math.pow(1 - k, 3);
        el.textContent = p.pre + nf(p.val * e, p.dec) + p.suf;
        if (k < 1) requestAnimationFrame(f); else el.textContent = final;
      })(t0);
    }, delay);
  }
  var counters = $$('[data-count]');
  if (counters.length && 'IntersectionObserver' in window && !R) {
    var co = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { co.unobserve(e.target); count(e.target, 0); } });
    }, { threshold: 0.6 });
    counters.forEach(function (c) { if (parse(c.textContent)) co.observe(c); });
  }

  /* ---------- Parallax ringan pada gambar hero ---------- */
  var hb = $('.hero__bg');
  if (hb && !R) {
    var pt = 0;
    var par = function () {
      pt = 0; var y = window.scrollY;
      if (y < window.innerHeight * 1.2) hb.style.transform = 'translate3d(0,' + Math.round(y * 0.18) + 'px,0)';
    };
    window.addEventListener('scroll', function () { if (!pt) pt = requestAnimationFrame(par); }, { passive: true });
  }

  /* ---------- Formulir: status loading saat kirim ---------- */
  $$('form[data-auth-form]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type="submit"]');
      if (b) { b.classList.add('is-loading'); b.setAttribute('aria-busy', 'true'); }
    });
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) $$('.btn.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); b.disabled = false; });
  });

  /* ---------- Pesan flash dari server ---------- */
  var fd = $('#flash-data');
  if (fd) {
    try {
      var items = JSON.parse(fd.textContent);
      if (items.length) {
        var t = items[items.length - 1].type;
        setTimeout(function () { toast(items.map(function (i) { return i.msg; }).join(' · '), t === 'error' ? 'error' : t === 'success' ? 'ok' : ''); }, 350);
      }
    } catch (e) {}
  }
})();
