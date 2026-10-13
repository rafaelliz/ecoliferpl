<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/auth_view.php';

$next = sanitize_next(isset($_REQUEST['next']) ? $_REQUEST['next'] : '');
if (current_user()) { redirect(next_targets()[$next !== '' ? $next : 'dashboard']); }

$errors = [];
$old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email(isset($_POST['email']) ? $_POST['email'] : '');
    $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $old['email'] = $email;
    if (!csrf_check()) {
        $errors['form'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($email === '' || $pass === '') {
        $errors['form'] = 'Email dan password wajib diisi.';
    } elseif (login_throttled($email)) {
        $errors['form'] = 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.';
    } else {
        $id = verify_credentials($email, $pass);
        if ($id === null) {
            record_login_failure($email);
            $errors['form'] = 'Email atau password salah.';
        } else {
            clear_login_failures($email);
            login_user($id);
            touch_last_login($id);
            $u = current_user();
            $dest = ($next === 'admin' && $u['role'] !== 'admin') ? 'dashboard' : ($next !== '' ? $next : 'dashboard');
            flash('success', 'Selamat datang, ' . $u['name'] . '!');
            redirect(next_targets()[$dest]);
        }
    }
}
render_auth('login', $errors, $old, $next);
