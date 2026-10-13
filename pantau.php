<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/habits.php';

$user = require_login('pantau');
page_open(['title' => 'Pantau Kebiasaan · Ecolife', 'active' => 'pantau', 'body' => 'page-app', 'csrf' => true]);
json_data_block('initial-state', pantau_state((int)$user['id']));
?>
<main class="app" id="main">
  <div class="w">
    <header class="pagehead" data-reveal>
      <p class="eyebrow">Pantau</p>
      <h1>Pantau kebiasaan harianmu</h1>
      <p class="lead">Centang yang sudah kamu lakukan hari ini, lalu simpan. Setiap kebiasaan memberi poin satu kali per hari dan tersimpan di akunmu.</p>
    </header>
    <div class="grid grid--2 split">
      <div>
        <article class="glass card" data-reveal>
          <h3>Kebiasaan hari ini</h3>
          <div id="acts" class="habits"></div>
          <div class="actions">
            <button class="btn btn--lime" id="sv" type="button"><span class="btn__label">Simpan hari ini</span><span class="btn__spinner" aria-hidden="true"></span></button>
            <button class="btn btn--ghost" id="rs" type="button"><span class="btn__label">Reset</span></button>
          </div>
        </article>
      </div>
      <div>
        <article class="glass card" data-reveal style="--d:80ms">
          <h3>Pencapaianmu</h3>
          <div class="stats stats--3">
            <div class="stat"><b id="tp">0</b><small>total poin</small></div>
            <div class="stat"><b id="hr">0</b><small>hari tercatat</small></div>
            <div class="stat"><b id="lv" class="stat__lv">Hijau Muda</b><small>level</small></div>
          </div>
          <div class="bar" id="pbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progres menuju level berikutnya"><i id="pb"></i></div>
          <p class="note" id="pn"></p>
          <p class="note">Level: Hijau Muda (kurang dari 50 poin), Hijau Aktif (50 sampai 149 poin), Hijau Inspiratif (150 poin ke atas).</p>
        </article>
        <article class="glass card" data-reveal style="--d:160ms">
          <h3>Riwayat pencatatan</h3>
          <div class="table-wrap">
            <table>
              <thead><tr><th>No.</th><th>Waktu</th><th>Poin</th></tr></thead>
              <tbody id="hb"></tbody>
            </table>
          </div>
        </article>
      </div>
    </div>
  </div>
</main>
<?php page_close(['js/pantau.js']);
