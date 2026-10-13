<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/predict.php';

$user = require_login('prediksi');
page_open(['title' => 'Prediksi · Ecolife', 'active' => 'prediksi', 'body' => 'page-app', 'csrf' => true]);
json_data_block('form-spec', eco_form_spec());
json_data_block('initial-history', prediction_history((int)$user['id']));
?>
<main class="app" id="main">
  <div class="w">
    <header class="pagehead" data-reveal>
      <p class="eyebrow">Prediksi</p>
      <h1>Prediksi tingkat kebiasaan ramah lingkungan</h1>
      <p class="lead">Isi data kebiasaanmu di bawah ini, lalu tekan Hitung prediksi.</p>
    </header>
    <article class="glass card" data-reveal>
      <form id="pform" novalidate>
        <div class="fg" id="ff"></div>
        <div class="actions">
          <button class="btn btn--lime" id="go" type="submit"><span class="btn__label">Hitung prediksi</span><span class="btn__arrow"><?= ic('arrow', 16) ?></span><span class="btn__spinner" aria-hidden="true"></span></button>
          <button class="btn btn--ghost" id="rp" type="button"><span class="btn__label">Reset</span></button>
        </div>
      </form>
    </article>
    <article class="glass card result" id="out" hidden aria-live="polite" tabindex="-1"></article>
    <article class="glass card">
      <h3>Riwayat prediksi</h3>
      <p class="note">Menampilkan maksimal 10 hasil terbaru yang tersimpan di akunmu.</p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>No.</th><th>Hasil</th><th>Tinggi</th><th>Sedang</th><th>Rendah</th><th>Waktu</th></tr></thead>
          <tbody id="pb2"></tbody>
        </table>
      </div>
    </article>
  </div>
</main>
<?php page_close(['js/select-ui.js', 'js/prediksi.js']);
