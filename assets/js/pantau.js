/* Halaman Pantau — checklist kebiasaan, poin, level, riwayat. Poin dihitung dan divalidasi di server. */
(function () {
  'use strict';
  var Eco = window.Eco, $ = Eco.$, $$ = Eco.$$, esc = Eco.esc;
  var state = JSON.parse($('#initial-state').textContent);
  var CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>';

  function renderActs() {
    var done = state.done || [];
    $('#acts').innerHTML = state.catalog.map(function (h) {
      var d = done.indexOf(h.key) > -1;
      return '<label class="habit' + (d ? ' habit--done' : '') + '" for="h-' + h.key + '">' +
        '<input class="habit__input" type="checkbox" id="h-' + h.key + '" value="' + h.key + '"' + (d ? ' checked disabled' : '') + '>' +
        '<span class="habit__box" aria-hidden="true">' + CHECK + '</span>' +
        '<span class="habit__label"><span class="habit__emoji">' + h.emoji + '</span>' + esc(h.label) +
        (d ? ' <span class="habit__done">· tercatat hari ini</span>' : '') + '</span>' +
        '<span class="badge">+' + h.points + '</span></label>';
    }).join('');
  }

  var lastLevel = state.level.name;
  function draw(prevPoints, animate) {
    var S = state, bar = $('#pbar');
    Eco.tween($('#tp'), prevPoints, S.points, 800);
    $('#hr').textContent = S.days;
    var lv = $('#lv'); lv.textContent = S.level.name;
    if (animate && S.level.name !== lastLevel) { lv.classList.remove('bump'); void lv.offsetWidth; lv.classList.add('bump'); }
    lastLevel = S.level.name;
    $('#pb').style.width = S.level.percent + '%';
    bar.setAttribute('aria-valuenow', Math.round(S.level.percent));
    $('#pn').textContent = S.level.next ? 'Tinggal ' + S.level.remaining + ' poin lagi menuju level berikutnya.' : 'Kamu sudah di level tertinggi. Pertahankan!';
    $('#hb').innerHTML = S.history.length
      ? S.history.map(function (x, i) {
          return '<tr' + (animate && i === 0 ? ' class="row-new"' : '') + '><td>' + (i + 1) + '</td><td>' + Eco.fmt(x.at) + '</td><td><span class="badge">+' + x.points + '</span></td></tr>';
        }).join('')
      : '<tr><td colspan="3" class="note">Belum ada catatan. Centang kebiasaan lalu simpan.</td></tr>';
  }

  /* render awal: lebar bar dimulai dari 0 agar teranimasi */
  renderActs();
  $('#pb').style.width = '0%';
  draw(0, false);

  var sv = $('#sv');
  sv.addEventListener('click', function () {
    var keys = $$('input.habit__input:checked:not(:disabled)').map(function (i) { return i.value; });
    if (!keys.length) { Eco.toast('Centang minimal satu kebiasaan.', 'error'); return; }
    Eco.busy(sv, true);
    Eco.api('api/pantau.php', { method: 'POST', body: { action: 'save', habits: keys } }).then(function (d) {
      Eco.busy(sv, false);
      if (d.ok) {
        var prev = state.points; state = d.state; renderActs(); draw(prev, true);
        Eco.toast('Tersimpan! +' + d.added + ' poin 🌱', 'ok');
      } else {
        if (d.state) { var p = state.points; state = d.state; renderActs(); draw(p, false); }
        Eco.toast(d.error || 'Gagal menyimpan. Coba lagi.', 'error');
      }
    });
  });

  var rs = $('#rs'), arm = 0;
  var rsLabel = rs.querySelector('.btn__label');
  rs.addEventListener('click', function () {
    if (!arm) {
      arm = setTimeout(function () { arm = 0; rsLabel.textContent = 'Reset'; }, 4000);
      rsLabel.textContent = 'Yakin? Klik lagi';
      return;
    }
    clearTimeout(arm); arm = 0; rsLabel.textContent = 'Reset';
    Eco.busy(rs, true);
    Eco.api('api/pantau.php', { method: 'POST', body: { action: 'reset' } }).then(function (d) {
      Eco.busy(rs, false);
      if (d.ok) {
        var prev = state.points; state = d.state; renderActs(); draw(prev, false);
        Eco.toast('Poin dan riwayat pencatatan sudah dihapus.', 'ok');
      } else { Eco.toast(d.error || 'Gagal mereset.', 'error'); }
    });
  });
})();
