/* Halaman Prediksi — form, hasil, riwayat. Perhitungan model dilakukan di server (api/predict.php). */
(function () {
  'use strict';
  var Eco = window.Eco, $ = Eco.$, esc = Eco.esc;
  var spec = JSON.parse($('#form-spec').textContent);
  var history = JSON.parse($('#initial-history').textContent);
  var TIP = {
    Tinggi: 'Pertahankan! Ajak teman ikut kebiasaan yang sama dan bagikan tipsmu.',
    Sedang: 'Sudah baik, tinggal ditingkatkan. Kurangi perjalanan pesawat/kendaraan pribadi dan perbanyak memilah sampah.',
    Rendah: 'Mulai dari langkah kecil: bawa tumbler dan tas belanja, kurangi plastik sekali pakai, dan coba memilah sampah.'
  };
  var CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>';
  var slug = function (k) { return k.replace(/\W/g, ''); };

  function buildForm() {
    var h = '';
    Object.keys(spec.selects).forEach(function (k) {
      var s = spec.selects[k], id = 's-' + slug(k);
      h += '<div class="f"><label for="' + id + '" id="' + id + '-l">' + esc(s.label) + '</label><select id="' + id + '" data-k="' + esc(k) + '">' +
        Object.keys(s.options).map(function (v) {
          return '<option value="' + esc(v) + '"' + (v === s.default ? ' selected' : '') + '>' + esc(s.options[v]) + '</option>';
        }).join('') + '</select></div>';
    });
    Object.keys(spec.numbers).forEach(function (k) {
      var n = spec.numbers[k], id = 'n-' + slug(k);
      h += '<div class="f"><label for="' + id + '">' + esc(n.label) + '</label><input id="' + id + '" type="number" inputmode="numeric" data-k="' + esc(k) +
        '" value="' + n.default + '" min="' + n.min + '" max="' + n.max + '"></div>';
    });
    h += '<div class="f f--wide"><span class="lbl" id="rec-l">Jenis sampah yang kamu daur ulang</span><div class="pills" role="group" aria-labelledby="rec-l">' +
      Object.keys(spec.recycle).map(function (k) {
        return '<label class="pillopt"><input type="checkbox" data-k="Recycle_' + k + '"><span class="pillopt__dot" aria-hidden="true">' + CHECK + '</span>' + esc(spec.recycle[k]) + '</label>';
      }).join('') + '</div></div>';
    var ff = $('#ff'); ff.innerHTML = h; Eco.enhanceSelects(ff);
  }

  function drawHistory(H) {
    $('#pb2').innerHTML = H.length ? H.map(function (x, i) {
      return '<tr><td>' + (i + 1) + '</td><td><b class="c-' + esc(x.c.toLowerCase()) + '">' + esc(x.c) + '</b></td><td>' + x.Tinggi + '%</td><td>' + x.Sedang + '%</td><td>' + x.Rendah + '%</td><td>' + Eco.fmt(x.t) + '</td></tr>';
    }).join('') : '<tr><td colspan="6" class="note">Belum ada riwayat prediksi.</td></tr>';
  }

  function showResult(top, result) {
    var out = $('#out'), pc = function (c) { return result.filter(function (x) { return x.c === c; })[0].p * 100; };
    out.hidden = false;
    out.style.animation = 'none'; void out.offsetWidth; out.style.animation = '';
    out.innerHTML = '<div class="res"><div class="note">Tingkat kebiasaan ramah lingkungan</div><div class="lv c-' + top.toLowerCase() + '">' + esc(top) + '</div></div>' +
      ['Tinggi', 'Sedang', 'Rendah'].map(function (c) {
        return '<div class="pr"><span>' + c + '</span><b>' + pc(c).toFixed(1) + '%</b></div>' +
          '<div class="bar bar--c c-' + c.toLowerCase() + '"><i style="--w:' + pc(c) + '%"></i></div>';
      }).join('') +
      '<div class="tip">💡 ' + esc(TIP[top]) + '</div><p class="note" style="margin:12px 0 0">Hasil bersifat perkiraan dari data sintetis.</p>';
    out.focus({ preventScroll: true });
    out.scrollIntoView({ behavior: Eco.reduced ? 'auto' : 'smooth', block: 'nearest' });
  }

  buildForm(); drawHistory(history);

  $('#rp').addEventListener('click', function () {
    buildForm(); $('#out').hidden = true; Eco.toast('Form dikembalikan ke nilai awal.');
  });

  var go = $('#go');
  $('#pform').addEventListener('submit', function (e) {
    e.preventDefault();
    var values = {};
    Eco.$$('#ff [data-k]').forEach(function (el) {
      values[el.dataset.k] = el.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
    });
    Eco.busy(go, true);
    var t0 = Date.now();
    Eco.api('api/predict.php', { method: 'POST', body: { values: values } }).then(function (d) {
      var wait = Eco.reduced ? 0 : Math.max(0, 450 - (Date.now() - t0)); // beri waktu agar efek loading terlihat
      setTimeout(function () {
        Eco.busy(go, false);
        if (d.ok) { showResult(d.top, d.result); drawHistory(d.history); }
        else { Eco.toast(d.error || 'Terjadi kesalahan, coba lagi.', 'error'); }
      }, wait);
    });
  });
})();
