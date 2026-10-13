<?php
/** Tampilan login/registrasi dua panel (satu markup untuk dua mode, dengan transisi geser). */

function auth_field($id, $label, $type, $name, $icon, $extra = '', $value = '', $error = '', $toggle = false)
{
    $err = $error !== '' ? '<p class="field__err" id="' . e($id) . '-err" role="alert">' . e($error) . '</p>' : '';
    $inv = $error !== '' ? ' aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '';
    $tog = $toggle
        ? '<button type="button" class="input__toggle" data-toggle-pass aria-label="Tampilkan password" aria-pressed="false">'
          . '<span class="i-show">' . ic('eye', 18) . '</span><span class="i-hide">' . ic('eyeoff', 18) . '</span></button>'
        : '';
    return '<div class="field' . ($error !== '' ? ' has-error' : '') . '"><label for="' . e($id) . '">' . e($label) . '</label>'
        . '<div class="input"><span class="input__ic">' . ic($icon, 18) . '</span>'
        . '<input id="' . e($id) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" ' . $extra . $inv . '>'
        . $tog . '</div>' . $err . '</div>';
}

function render_auth($mode, array $errors, array $old, $next)
{
    $isLogin = $mode === 'login';
    $q = $next !== '' ? '?next=' . urlencode($next) : '';
    $flash = take_flash();

    page_open([
        'title' => ($isLogin ? 'Masuk' : 'Daftar') . ' · Ecolife',
        'body' => 'page-auth', 'nav' => false, 'footer' => false,
    ]);
    echo '<main class="auth dark-scope" id="main">' . nature_picture('auth__bg') . '<div class="auth__shade"></div>';
    echo '<a class="brand auth__home" href="' . e(url('index.php')) . '"><img src="' . e(url('assets/img/logo.png')) . '" alt="" width="36" height="36"><span>Eco<b>life</b></span></a>';
    echo '<button type="button" id="theme" class="iconbtn auth__theme" aria-label="Ganti tema terang atau gelap">' . ic('theme') . '</button>';
    echo '<div class="auth__card glass" id="authCard" data-mode="' . e($mode) . '">';

    /* Panel login */
    echo '<section class="auth__pane auth__pane--login" id="paneLogin" aria-labelledby="loginTitle"' . ($isLogin ? '' : ' inert aria-hidden="true"') . '>';
    echo '<h1 id="loginTitle">Masuk</h1><p class="auth__sub">Lanjutkan kebiasaan hijaumu hari ini.</p>';
    foreach ($flash as $f) {
        echo '<div class="alert alert--' . e($f['type']) . '" role="status">' . e($f['msg']) . '</div>';
    }
    if ($isLogin && isset($errors['form'])) {
        echo '<div class="alert alert--error" role="alert">' . e($errors['form']) . '</div>';
    }
    echo '<form method="post" action="' . e(url('login.php') . $q) . '" data-auth-form>' . csrf_field();
    echo auth_field('l-email', 'Email', 'email', 'email', 'mail', 'required maxlength="150" autocomplete="email" inputmode="email"', $isLogin && isset($old['email']) ? $old['email'] : '');
    echo auth_field('l-pass', 'Password', 'password', 'password', 'lock', 'required autocomplete="current-password"', '', '', true);
    echo '<button class="btn btn--lime btn--block" type="submit"><span class="btn__label">Masuk</span><span class="btn__arrow">' . ic('arrow', 16) . '</span><span class="btn__spinner" aria-hidden="true"></span></button>';
    echo '</form><p class="auth__alt">Belum punya akun? <a href="' . e(url('register.php') . $q) . '" data-switch="register">Daftar</a></p></section>';

    /* Panel registrasi */
    echo '<section class="auth__pane auth__pane--register" id="paneRegister" aria-labelledby="registerTitle"' . ($isLogin ? ' inert aria-hidden="true"' : '') . '>';
    echo '<h1 id="registerTitle">Buat akun</h1><p class="auth__sub">Gratis. Cukup nama, email, dan password.</p>';
    if (!$isLogin && isset($errors['form'])) {
        echo '<div class="alert alert--error" role="alert">' . e($errors['form']) . '</div>';
    }
    echo '<form method="post" action="' . e(url('register.php') . $q) . '" data-auth-form data-register>' . csrf_field();
    echo auth_field('r-name', 'Nama', 'text', 'name', 'user', 'required minlength="2" maxlength="60" autocomplete="name"', !$isLogin && isset($old['name']) ? $old['name'] : '', isset($errors['name']) ? $errors['name'] : '');
    echo auth_field('r-email', 'Email', 'email', 'email', 'mail', 'required maxlength="150" autocomplete="email" inputmode="email"', !$isLogin && isset($old['email']) ? $old['email'] : '', isset($errors['email']) ? $errors['email'] : '');
    echo auth_field('r-pass', 'Password', 'password', 'password', 'lock', 'required minlength="8" maxlength="72" autocomplete="new-password"', '', isset($errors['password']) ? $errors['password'] : '', true);
    echo '<p class="hint">Minimal 8 karakter, mengandung huruf dan angka.</p>';
    echo auth_field('r-pass2', 'Ulangi password', 'password', 'password2', 'lock', 'required minlength="8" maxlength="72" autocomplete="new-password"', '', isset($errors['password2']) ? $errors['password2'] : '');
    echo '<button class="btn btn--lime btn--block" type="submit"><span class="btn__label">Daftar</span><span class="btn__arrow">' . ic('arrow', 16) . '</span><span class="btn__spinner" aria-hidden="true"></span></button>';
    echo '</form><p class="auth__alt">Sudah punya akun? <a href="' . e(url('login.php') . $q) . '" data-switch="login">Masuk</a></p></section>';

    /* Panel sambutan (bergeser antar sisi) */
    echo '<aside class="auth__overlay" aria-label="Sambutan Ecolife">';
    echo '<div class="overlay__txt overlay__txt--login"><p class="eyebrow">Ecolife</p><h2>Halo,<br>Sahabat Hijau!</h2>'
        . '<p>Daftar dan mulai catat kebiasaan konsumsi ramah lingkunganmu, kumpulkan poin, lalu lihat prediksinya.</p>'
        . '<a class="btn btn--glass" href="' . e(url('register.php') . $q) . '" data-switch="register"><span class="btn__label">Buat akun</span><span class="btn__arrow">' . ic('arrow', 14) . '</span></a></div>';
    echo '<div class="overlay__txt overlay__txt--register"><p class="eyebrow">Ecolife</p><h2>Selamat<br>datang kembali!</h2>'
        . '<p>Sudah punya akun? Masuk untuk melanjutkan pencatatan poin dan riwayat kebiasaanmu.</p>'
        . '<a class="btn btn--glass" href="' . e(url('login.php') . $q) . '" data-switch="login"><span class="btn__label">Masuk</span><span class="btn__arrow">' . ic('arrow', 14) . '</span></a></div>';
    echo '</aside></div></main>';
    page_close(['js/auth.js'], false);
}
