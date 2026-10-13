<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/habits.php';
require __DIR__ . '/includes/predict.php';

$user = require_login('dashboard');
$uid = (int)$user['id'];
$state = pantau_state($uid);
$preds = prediction_history($uid, 1);
$last = $preds ? $preds[0] : null;
$lv = $state['level'];
$hist = array_slice($state['history'], 0, 5);

function fmt_dt($iso)
{
    $t = strtotime($iso);
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return (int)date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ', ' . date('H.i', $t);
}

page_open(['title' => 'Dashboard · Ecolife', 'active' => 'dashboard', 'body' => 'page-app', 'csrf' => true]);
?>
<main class="app" id="main">
  <div class="w">
    <header class="pagehead" data-reveal>
      <p class="eyebrow">Dashboard</p>
      <h1>Halo, <?= e($user['name']) ?> 🌱</h1>
      <p class="lead">Ringkasan kebiasaan ramah lingkunganmu. Lanjutkan pencatatan hari ini atau coba prediksi baru.</p>
    </header>

    <div class="grid grid--3 dash-top">
      <article class="glass card" data-reveal>
        <p class="note">Total poin</p>
        <b class="bignum"><?= (int)$state['points'] ?></b>
        <span class="levelchip <?= e(level_label_class($lv['name'])) ?>"><?= e($lv['name']) ?></span>
        <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)round($lv['percent']) ?>" aria-label="Progres menuju level berikutnya"><i style="--w:<?= round($lv['percent'], 1) ?>%"></i></div>
        <p class="note"><?= $lv['next'] ? 'Tinggal ' . (int)$lv['remaining'] . ' poin lagi menuju level berikutnya.' : 'Kamu sudah di level tertinggi. Pertahankan!' ?></p>
      </article>
      <article class="glass card" data-reveal style="--d:80ms">
        <p class="note">Hari tercatat</p>
        <b class="bignum"><?= (int)$state['days'] ?></b>
        <p class="note">Jumlah hari berbeda saat kamu mencatat kebiasaan.</p>
      </article>
      <article class="glass card" data-reveal style="--d:160ms">
        <p class="note">Prediksi terakhir</p>
        <?php if ($last): ?>
          <b class="bignum res-<?= e(strtolower($last['c'])) ?>"><?= e($last['c']) ?></b>
          <p class="note">Tinggi <?= e($last['Tinggi']) ?>% · Sedang <?= e($last['Sedang']) ?>% · Rendah <?= e($last['Rendah']) ?>%</p>
        <?php else: ?>
          <b class="bignum bignum--muted">–</b>
          <p class="note">Belum ada prediksi. Coba hitung sekarang.</p>
        <?php endif; ?>
      </article>
    </div>

    <div class="grid grid--2 shortcuts">
      <a class="glass card shortcut" href="<?= e(url('pantau.php')) ?>" data-reveal>
        <span class="shortcut__ic"><?= ic('clip', 26) ?></span>
        <div><h3>Pantau kebiasaan</h3><p>Centang kebiasaan hari ini dan kumpulkan poin.</p></div>
        <span class="shortcut__go"><?= ic('arrow', 18) ?></span>
      </a>
      <a class="glass card shortcut" href="<?= e(url('prediksi.php')) ?>" data-reveal style="--d:80ms">
        <span class="shortcut__ic"><?= ic('chart', 26) ?></span>
        <div><h3>Prediksi</h3><p>Perkirakan tingkat kebiasaan ramah lingkunganmu.</p></div>
        <span class="shortcut__go"><?= ic('arrow', 18) ?></span>
      </a>
    </div>

    <article class="glass card" data-reveal>
      <div class="card__head"><h3>Riwayat pencatatan</h3><a class="link" href="<?= e(url('pantau.php')) ?>">Lihat semua</a></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>No.</th><th>Waktu</th><th>Poin</th></tr></thead>
          <tbody>
          <?php if (!$hist): ?>
            <tr><td colspan="3" class="note">Belum ada catatan. Mulai dari halaman Pantau.</td></tr>
          <?php endif; foreach ($hist as $i => $h): ?>
            <tr><td><?= $i + 1 ?></td><td><?= e(fmt_dt($h['at'])) ?></td><td><span class="badge">+<?= (int)$h['points'] ?></span></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </article>

    <div class="grid grid--2 account" data-reveal>
      <article class="glass card"><h3>Akun</h3><p class="note"><?= e($user['email']) ?></p>
        <p><a class="btn btn--ghost btn--sm" href="<?= e(url('profil.php')) ?>"><span class="btn__label">Kelola profil</span></a></p></article>
      <article class="glass card"><h3>Keluar</h3><p class="note">Akhiri sesi di perangkat ini.</p>
        <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><span class="btn__label">Keluar</span></button></form></article>
    </div>
  </div>
</main>
<?php page_close();
