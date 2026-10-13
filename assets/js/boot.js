/* Dijalankan di <head>: menandai JS aktif dan menerapkan tema tersimpan sebelum halaman tampil (tanpa kedip). */
(function () {
  var d = document.documentElement;
  d.classList.add('js');
  try {
    var t = localStorage.getItem('ecolife_theme'); // hanya preferensi tampilan, bukan data akun
    d.setAttribute('data-theme', t === 'light' || t === 'dark' ? t : 'dark');
  } catch (e) { d.setAttribute('data-theme', 'dark'); }
})();
