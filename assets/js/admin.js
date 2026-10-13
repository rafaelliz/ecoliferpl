/* Konfirmasi sebelum tindakan admin yang mengubah data. */
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  });
});
