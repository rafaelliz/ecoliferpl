<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/habits.php';

$user = current_user();
$habits = habit_catalog();

page_open([
    'title' => 'Ecolife | Edukasi dan Pemantauan Kebiasaan Konsumsi Ramah Lingkungan',
    'desc' => 'Ecolife adalah website edukasi dan pemantauan kebiasaan harian. Pelajari kaitan konsumsi dengan sampah, kumpulkan poin dari kebiasaan baik, lalu perkirakan tingkat kebiasaan ramah lingkunganmu.',
    'active' => '', 'landing' => true, 'body' => 'page-landing',
]);
?>
<main id="main">
  <!-- HERO -->
  <section class="hero dark-scope" id="beranda" data-section>
    <div class="hero__frame">
      <?= nature_picture('hero__bg') ?>
      <div class="hero__shade"></div>
      <div class="hero__content">
        <p class="pill hero__in" style="--i:0">Universitas Halu Oleo, Fakultas MIPA, Rekayasa Perangkat Lunak</p>
        <h1 class="hero__in" style="--i:1">Kenali dan catat kebiasaan konsumsi yang ramah lingkungan</h1>
        <p class="hero__lead hero__in" style="--i:2">Ecolife adalah website edukasi dan pemantauan kebiasaan harian. Pelajari kaitan konsumsi dengan sampah, kumpulkan poin dari kebiasaan baik, lalu perkirakan tingkat kebiasaan ramah lingkunganmu.</p>
        <div class="hero__cta hero__in" style="--i:3">
          <a class="btn btn--white" href="<?= e(url('prediksi.php')) ?>"><span class="btn__label">Coba prediksi</span><span class="btn__arrow"><?= ic('arrow', 16) ?></span></a>
          <a class="btn btn--glass" href="<?= e(url('pantau.php')) ?>"><span class="btn__label">Catat kebiasaan</span></a>
        </div>
        <?php if (!$user): ?>
          <p class="hero__note hero__in" style="--i:4">Fitur Pantau dan Prediksi memerlukan akun. <a href="<?= e(url('register.php')) ?>">Daftar gratis</a> atau <a href="<?= e(url('login.php')) ?>">masuk</a>.</p>
        <?php else: ?>
          <p class="hero__note hero__in" style="--i:4">Halo, <?= e($user['name']) ?>! <a href="<?= e(url('dashboard.php')) ?>">Buka dashboard</a></p>
        <?php endif; ?>
      </div>
      <div class="hero__cards">
        <article class="glass statcard hero__in" style="--i:5">
          <b class="statcard__n" data-count>33,79 jt ton</b>
          <p>timbulan sampah Indonesia 2024</p>
        </article>
        <article class="glass statcard hero__in" style="--i:6">
          <b class="statcard__n" data-count>50,8%</b>
          <p>berasal dari rumah tangga</p>
        </article>
      </div>
    </div>
  </section>

  <!-- TENTANG -->
  <section class="section" id="tentang" data-section>
    <div class="w">
      <header class="section__head" data-reveal>
        <p class="eyebrow">Tentang</p>
        <h2>Mengapa kebiasaan konsumsi penting?</h2>
        <p class="lead">Rumah tangga adalah sumber sampah terbesar, jadi pilihan kecil sehari-hari berpengaruh langsung. Angka SIPSN dapat berubah karena bergantung pada jumlah kabupaten/kota yang melapor.</p>
      </header>
      <div class="grid grid--2">
        <article class="glass card" data-reveal>
          <h3>Mulai dari kebiasaan kecil</h3>
          <ul class="tips">
            <li><span>🥤</span>Bawa tumbler dan botol minum sendiri.</li>
            <li><span>🛍️</span>Bawa tas belanja, tolak kantong plastik.</li>
            <li><span>🚫</span>Tolak sedotan dan alat makan sekali pakai.</li>
            <li><span>♻️</span>Pakai kembali barang yang masih layak.</li>
            <li><span>🗑️</span>Pilah sampah organik, plastik, kertas, dan logam.</li>
          </ul>
        </article>
        <article class="glass card" data-reveal style="--d:80ms">
          <h3>Dari mana sampah rumah tangga?</h3>
          <div class="stats">
            <div class="stat"><b data-count>39,36%</b><small>berupa sisa makanan</small></div>
            <div class="stat"><b data-count>19,64%</b><small>berupa sampah plastik</small></div>
          </div>
          <h3 class="mt">Sumber data</h3>
          <ul class="links">
            <li><a href="https://databoks.katadata.co.id/en/environment/statistics/67fcf855d9122/indonesias-waste-generation-reaches-3379-million-tons-in-2024" target="_blank" rel="noopener">Databoks: timbulan sampah 2024</a></li>
            <li><a href="https://sipsn.menlhk.go.id/sipsn/" target="_blank" rel="noopener">SIPSN KLHK</a></li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <!-- ALUR -->
  <section class="section section--tint" id="alur" data-section>
    <div class="w">
      <header class="section__head" data-reveal>
        <p class="eyebrow">Alur</p>
        <h2>Bagaimana Ecolife bekerja?</h2>
        <p class="lead">Empat langkah dari belajar sampai mendapat hasil perkiraan.</p>
      </header>
      <div class="grid grid--4">
        <?php
        $steps = [
            ['Pelajari', 'Baca data sampah dan kebiasaan sederhana yang bisa dimulai hari ini.'],
            ['Catat', 'Centang kebiasaan yang sudah dilakukan, lalu simpan untuk mengumpulkan poin.'],
            ['Isi data gaya hidup', 'Masukkan pola makan, transportasi, sampah, dan energi di halaman prediksi.'],
            ['Lihat hasil', 'Model Logistic Regression menampilkan tingkat Rendah, Sedang, atau Tinggi beserta persentasenya.'],
        ];
        foreach ($steps as $i => $s): ?>
          <article class="glass card step" data-reveal style="--d:<?= $i * 80 ?>ms">
            <span class="step__n"><?= $i + 1 ?></span>
            <h3><?= e($s[0]) ?></h3>
            <p><?= e($s[1]) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- MODEL -->
  <section class="section" id="model" data-section>
    <div class="w">
      <header class="section__head" data-reveal>
        <p class="eyebrow">Model</p>
        <h2>Tentang model prediksi</h2>
        <p class="lead">Perhitungan dijalankan di server Ecolife setelah kamu masuk. Yang disimpan ke riwayat hanya hasilnya (tingkat dan persentase), bukan data isianmu.</p>
      </header>
      <div class="grid grid--2">
        <article class="glass card" data-reveal>
          <h3>Logistic Regression</h3>
          <div class="stats stats--3">
            <div class="stat"><b data-count>±88,8%</b><small>akurasi data uji</small></div>
            <div class="stat"><b data-count>3</b><small>kelas hasil</small></div>
            <div class="stat"><b data-count>58</b><small>fitur input</small></div>
          </div>
          <p class="note">17 fitur numerik (distandarkan) dan 41 fitur kategorikal (one-hot). Skor tiap kelas diubah menjadi persentase dengan fungsi softmax.</p>
        </article>
        <article class="glass card" data-reveal style="--d:80ms">
          <h3>Dataset dan batasannya</h3>
          <p>Model dilatih dari dataset Kaggle <i>Individual Carbon Footprint Calculation</i>. Data tersebut <b>sintetis</b>, jadi hasilnya adalah prototipe untuk pembelajaran, bukan penilaian pasti.</p>
          <p class="note">Akurasi menunjukkan persentase tebakan kelas yang benar pada data yang tidak dipakai untuk melatih model.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- PANTAU -->
  <section class="section section--tint" id="pantau" data-section>
    <div class="w feature">
      <div class="feature__txt" data-reveal>
        <p class="eyebrow">Pantau</p>
        <h2>Pantau kebiasaan harianmu</h2>
        <p class="lead">Centang yang sudah kamu lakukan hari ini, lalu simpan. Poin, level, dan riwayatmu tersimpan di akunmu.</p>
        <a class="btn btn--lime" href="<?= e(url('pantau.php')) ?>"><span class="btn__label"><?= $user ? 'Buka Pantau' : 'Masuk untuk mulai' ?></span><span class="btn__arrow"><?= ic('arrow', 16) ?></span></a>
      </div>
      <div class="glass card feature__card" data-reveal style="--d:100ms">
        <p class="note feature__badge"><?= ic('lock', 14) ?> Perlu akun</p>
        <?php foreach ($habits as $h): ?>
          <div class="habit habit--static"><span class="habit__label"><span class="habit__emoji"><?= $h['emoji'] ?></span><?= e($h['label']) ?></span><span class="badge">+<?= (int)$h['points'] ?></span></div>
        <?php endforeach; ?>
        <p class="note">Level: Hijau Muda (kurang dari 50 poin), Hijau Aktif (50 sampai 149 poin), Hijau Inspiratif (150 poin ke atas).</p>
      </div>
    </div>
  </section>

  <!-- PREDIKSI -->
  <section class="section" id="prediksi" data-section>
    <div class="w feature feature--rev">
      <div class="feature__txt" data-reveal>
        <p class="eyebrow">Prediksi</p>
        <h2>Prediksi tingkat kebiasaan ramah lingkungan</h2>
        <p class="lead">Isi data kebiasaanmu, lalu tekan Hitung prediksi untuk melihat tingkat Rendah, Sedang, atau Tinggi beserta persentasenya.</p>
        <a class="btn btn--lime" href="<?= e(url('prediksi.php')) ?>"><span class="btn__label"><?= $user ? 'Buka Prediksi' : 'Masuk untuk mulai' ?></span><span class="btn__arrow"><?= ic('arrow', 16) ?></span></a>
      </div>
      <div class="glass card feature__card" data-reveal style="--d:100ms">
        <p class="note feature__badge"><?= ic('lock', 14) ?> Perlu akun</p>
        <h3>Data yang kamu isi</h3>
        <div class="chips">
          <?php foreach (['Pola makan', 'Transportasi utama', 'Jenis kendaraan', 'Naik pesawat', 'Ukuran kantong sampah', 'Alat hemat energi', 'Sumber energi', 'Belanja bulanan', 'Jarak kendaraan', 'Jumlah kantong sampah', 'Pakaian baru', 'Jenis sampah didaur ulang'] as $c): ?>
            <span class="chip"><?= e($c) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- TIM -->
  <section class="section section--tint" id="tim" data-section>
    <div class="w">
      <header class="section__head" data-reveal>
        <p class="eyebrow">Tim</p>
        <h2>Tim pengembang</h2>
        <p class="lead">Dibuat untuk tugas mata kuliah Rekayasa Perangkat Lunak, Program Studi Ilmu Komputer, Universitas Halu Oleo.</p>
      </header>
      <div class="grid grid--3">
        <?php
        $team = [
            ['Maya Ulfa Zakiyah', 'F1G125012', 'tim-maya.jpg'],
            ['Rafael Benaya Wulumohitu', 'F1G125073', 'tim-rafael.jpg'],
            ['Afra Naila Arkarna', 'F1G125022', 'tim-afra.jpg'],
        ];
        foreach ($team as $i => $m): ?>
          <article class="glass card mem" data-reveal style="--d:<?= $i * 80 ?>ms">
            <img class="avatar-lg" src="<?= e(url('assets/img/' . $m[2])) ?>" alt="Foto <?= e($m[0]) ?>" width="84" height="84" loading="lazy">
            <div><h3><?= e($m[0]) ?></h3><p class="note">NIM <?= e($m[1]) ?></p></div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php page_close();
