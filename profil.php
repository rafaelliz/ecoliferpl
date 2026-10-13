<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';

$user = require_login('profil');
$uid = (int)$user['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if (!csrf_check()) {
        flash('error', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        redirect('profil.php');
    }
    if ($action === 'name') {
        list($name, $err) = validate_name(isset($_POST['name']) ? $_POST['name'] : '');
        if ($err) { $errors['name'] = $err; }
        else {
            $st = db()->prepare('UPDATE users SET name = ? WHERE id = ?');
            $st->execute([$name, $uid]);
            flash('success', 'Nama berhasil diperbarui.');
            redirect('profil.php');
        }
    } elseif ($action === 'password') {
        $cur = isset($_POST['current']) && is_string($_POST['current']) ? $_POST['current'] : '';
        $new = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $new2 = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$uid]);
        $hash = (string)$st->fetchColumn();
        if (!password_verify($cur, $hash)) { $errors['current'] = 'Password saat ini salah.'; }
        elseif ($e = validate_password($new)) { $errors['password'] = $e; }
        elseif ($new !== $new2) { $errors['password2'] = 'Konfirmasi password tidak sama.'; }
        else {
            $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $up->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password berhasil diganti.');
            redirect('profil.php');
        }
    }
}

$user = current_user(true);
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$t = strtotime($user['created_at']);
$since = (int)date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);

function pf($id, $label, $name, $type, $auto, $err, $value = '')
{
    return '<div class="field' . ($err ? ' has-error' : '') . '"><label for="' . $id . '">' . e($label) . '</label>'
        . '<div class="input"><input id="' . $id . '" type="' . $type . '" name="' . $name . '" value="' . e($value) . '" autocomplete="' . $auto . '" required'
        . ($type === 'password' ? ' maxlength="72"' : ' maxlength="60"') . ($err ? ' aria-invalid="true"' : '') . '></div>'
        . ($err ? '<p class="field__err" role="alert">' . e($err) . '</p>' : '') . '</div>';
}

page_open(['title' => 'Profil · Ecolife', 'active' => '', 'body' => 'page-app', 'csrf' => true]);
?>
<main class="app" id="main">
  <div class="w w--narrow">
    <header class="pagehead" data-reveal>
      <p class="eyebrow">Profil</p>
      <h1>Profil pengguna</h1>
      <p class="lead">Kelola nama dan password akunmu.</p>
    </header>
    <article class="glass card" data-reveal>
      <h3>Informasi akun</h3>
      <dl class="info">
        <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
        <div><dt>Peran</dt><dd><span class="badge"><?= $user['role'] === 'admin' ? 'Admin' : 'Pengguna' ?></span></dd></div>
        <div><dt>Bergabung</dt><dd><?= e($since) ?></dd></div>
      </dl>
    </article>
    <article class="glass card" data-reveal>
      <h3>Ubah nama</h3>
      <form method="post" action="profil.php" class="form" data-auth-form>
        <?= csrf_field() ?><input type="hidden" name="action" value="name">
        <?= pf('p-name', 'Nama', 'name', 'text', 'name', isset($errors['name']) ? $errors['name'] : '', $user['name']) ?>
        <button class="btn btn--lime" type="submit"><span class="btn__label">Simpan nama</span><span class="btn__spinner" aria-hidden="true"></span></button>
      </form>
    </article>
    <article class="glass card" data-reveal>
      <h3>Ganti password</h3>
      <form method="post" action="profil.php" class="form" data-auth-form>
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <?= pf('p-cur', 'Password saat ini', 'current', 'password', 'current-password', isset($errors['current']) ? $errors['current'] : '') ?>
        <?= pf('p-new', 'Password baru', 'password', 'password', 'new-password', isset($errors['password']) ? $errors['password'] : '') ?>
        <p class="hint">Minimal 8 karakter, mengandung huruf dan angka.</p>
        <?= pf('p-new2', 'Ulangi password baru', 'password2', 'password', 'new-password', isset($errors['password2']) ? $errors['password2'] : '') ?>
        <button class="btn btn--lime" type="submit"><span class="btn__label">Ganti password</span><span class="btn__spinner" aria-hidden="true"></span></button>
      </form>
    </article>
  </div>
</main>
<?php page_close();
