<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';

$admin = require_admin();      // role dibaca dari database pada setiap request
$adminId = (int)$admin['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        redirect('admin/index.php');
    }
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $target = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $back = 'admin/index.php' . (isset($_POST['q']) && $_POST['q'] !== '' ? '?q=' . urlencode((string)$_POST['q']) : '');

    $st = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ?');
    $st->execute([$target]);
    $row = $st->fetch();

    if (!$row) {
        flash('error', 'Pengguna tidak ditemukan.');
    } elseif ($target === $adminId) {
        flash('error', 'Kamu tidak dapat mengubah atau menghapus akunmu sendiri dari halaman ini.');
    } elseif ($action === 'toggle_role') {
        $newRole = $row['role'] === 'admin' ? 'user' : 'admin';
        $up = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $up->execute([$newRole, $target]);
        flash('success', $row['name'] . ' sekarang berperan sebagai ' . ($newRole === 'admin' ? 'Admin' : 'Pengguna') . '.');
    } elseif ($action === 'delete_user') {
        $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$target]);
        flash('success', 'Akun ' . $row['name'] . ' beserta datanya telah dihapus.');
    } else {
        flash('error', 'Tindakan tidak dikenal.');
    }
    redirect($back);
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$per = 15;
$where = '';
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where = ' WHERE u.name LIKE ? OR u.email LIKE ?';
    $params = [$like, $like];
}
$st = $pdo->prepare('SELECT COUNT(*) FROM users u' . $where);
$st->execute($params);
$totalFiltered = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($totalFiltered / $per));
$page = min($page, $pages);
$offset = ($page - 1) * $per;

$st = $pdo->prepare('SELECT u.id, u.name, u.email, u.role, u.created_at, u.last_login_at,
    (SELECT COALESCE(SUM(h.total_points), 0) FROM habit_entries h WHERE h.user_id = u.id) AS points,
    (SELECT COUNT(*) FROM predictions p WHERE p.user_id = u.id) AS preds
    FROM users u' . $where . ' ORDER BY u.id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset);
$st->execute($params);
$users = $st->fetchAll();

$stats = [
    'Pengguna' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Admin' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'Pencatatan' => (int)$pdo->query('SELECT COUNT(*) FROM habit_entries')->fetchColumn(),
    'Prediksi' => (int)$pdo->query('SELECT COUNT(*) FROM predictions')->fetchColumn(),
];

function adm_date($dt)
{
    return $dt ? date('d/m/Y H.i', strtotime($dt)) : '–';
}
function adm_page_url($p, $q)
{
    return 'index.php?page=' . (int)$p . ($q !== '' ? '&q=' . urlencode($q) : '');
}

page_open(['title' => 'Admin · Ecolife', 'active' => '', 'body' => 'page-app', 'csrf' => true]);
?>
<main class="app" id="main">
  <div class="w">
    <header class="pagehead" data-reveal>
      <p class="eyebrow">Admin</p>
      <h1>Kelola pengguna</h1>
      <p class="lead">Daftar akun Ecolife. Perubahan role dan penghapusan akun hanya dapat dilakukan oleh admin.</p>
    </header>

    <div class="grid grid--4 admin-stats" data-reveal>
      <?php foreach ($stats as $label => $n): ?>
        <article class="glass card"><p class="note"><?= e($label) ?></p><b class="bignum"><?= $n ?></b></article>
      <?php endforeach; ?>
    </div>

    <article class="glass card" data-reveal>
      <form class="searchbar" method="get" action="index.php" role="search">
        <div class="input"><span class="input__ic"><?= ic('search', 18) ?></span>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau email" aria-label="Cari pengguna" maxlength="100"></div>
        <button class="btn btn--lime btn--sm" type="submit"><span class="btn__label">Cari</span></button>
      </form>
      <div class="table-wrap">
        <table class="table--admin">
          <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Poin</th><th>Prediksi</th><th>Bergabung</th><th>Login terakhir</th><th><span class="sr">Aksi</span></th></tr></thead>
          <tbody>
          <?php if (!$users): ?><tr><td colspan="8" class="note">Tidak ada pengguna yang cocok.</td></tr><?php endif; ?>
          <?php foreach ($users as $u): $self = (int)$u['id'] === $adminId; ?>
            <tr>
              <td><b><?= e($u['name']) ?></b><?= $self ? ' <span class="note">(kamu)</span>' : '' ?></td>
              <td><?= e($u['email']) ?></td>
              <td><span class="badge <?= $u['role'] === 'admin' ? 'badge--lime' : '' ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Pengguna' ?></span></td>
              <td><?= (int)$u['points'] ?></td>
              <td><?= (int)$u['preds'] ?></td>
              <td><?= e(adm_date($u['created_at'])) ?></td>
              <td><?= e(adm_date($u['last_login_at'])) ?></td>
              <td class="rowact">
                <?php if (!$self): ?>
                  <form method="post" action="index.php" data-confirm="Ubah role <?= e($u['name']) ?>?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle_role"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                    <button class="btn btn--ghost btn--xs" type="submit"><span class="btn__label"><?= $u['role'] === 'admin' ? 'Jadikan pengguna' : 'Jadikan admin' ?></span></button>
                  </form>
                  <form method="post" action="index.php" data-confirm="Hapus akun <?= e($u['name']) ?> beserta seluruh datanya? Tindakan ini tidak dapat dibatalkan.">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                    <button class="btn btn--danger btn--xs" type="submit" aria-label="Hapus <?= e($u['name']) ?>"><span class="btn__label"><?= ic('trash', 14) ?> Hapus</span></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Halaman">
          <?php if ($page > 1): ?><a class="btn btn--ghost btn--xs" href="<?= e(adm_page_url($page - 1, $q)) ?>"><span class="btn__label">Sebelumnya</span></a><?php endif; ?>
          <span class="note">Halaman <?= $page ?> dari <?= $pages ?></span>
          <?php if ($page < $pages): ?><a class="btn btn--ghost btn--xs" href="<?= e(adm_page_url($page + 1, $q)) ?>"><span class="btn__label">Berikutnya</span></a><?php endif; ?>
        </nav>
      <?php endif; ?>
    </article>
  </div>
</main>
<?php page_close(['js/admin.js']);
