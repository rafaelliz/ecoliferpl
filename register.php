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
    list($name, $nameErr) = validate_name(isset($_POST['name']) ? $_POST['name'] : '');
    $email = normalize_email(isset($_POST['email']) ? $_POST['email'] : '');
    $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $pass2 = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';
    $old = ['name' => $name, 'email' => $email];

    if (!csrf_check()) {
        $errors['form'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        if ($nameErr) { $errors['name'] = $nameErr; }
        if ($e1 = validate_email_addr($email)) { $errors['email'] = $e1; }
        if ($e2 = validate_password($pass)) { $errors['password'] = $e2; }
        elseif ($pass !== $pass2) { $errors['password2'] = 'Konfirmasi password tidak sama.'; }

        if (!$errors) {
            $id = create_user($name, $email, $pass);
            if ($id === false) {
                $errors['email'] = 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.';
            } else {
                login_user($id);
                touch_last_login($id);
                flash('success', 'Akun berhasil dibuat. Selamat datang, ' . $name . '!');
                redirect(next_targets()[$next !== '' && $next !== 'admin' ? $next : 'dashboard']);
            }
        }
    }
}
render_auth('register', $errors, $old, $next);
