<?php
/** Layout bersama: <head>, navbar, footer, ikon. */

function ic($name, $size = 20)
{
    static $p = [
        'arrow'   => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'user'    => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 8 9 6 9-6"/>',
        'lock'    => '<rect x="4" y="11" width="16" height="10" rx="3"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'eye'     => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eyeoff'  => '<path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a9.9 9.9 0 0 0 4.4-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'theme'   => '<circle cx="12" cy="12" r="9"/><path d="M12 3v18"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor" stroke="none"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'up'      => '<path d="m6 15 6-6 6 6"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'grid'    => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',
        'shield'  => '<path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'clip'    => '<rect x="5" y="4" width="14" height="17" rx="3"/><path d="M9 4h6v3H9z"/><path d="m9 13 2 2 4-4"/>',
        'chart'   => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];
    $d = isset($p[$name]) ? $p[$name] : '';
    return '<svg class="ic" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Gambar latar alam (picture webp + jpg). */
function nature_picture($class = '', $eager = true)
{
    return '<picture class="' . e($class) . '">'
        . '<source type="image/webp" srcset="' . e(url('assets/img/fern-1000.webp')) . ' 1000w, ' . e(url('assets/img/fern-1920.webp')) . ' 1920w" sizes="100vw">'
        . '<img src="' . e(url('assets/img/fern-1920.jpg')) . '" srcset="' . e(url('assets/img/fern-1000.jpg')) . ' 1000w, ' . e(url('assets/img/fern-1920.jpg')) . ' 1920w" sizes="100vw" alt="" '
        . 'width="1920" height="1138" decoding="async"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . '></picture>';
}

/**
 * Opsi: title, desc, body (class), active (menu aktif), nav (bool), csrf (bool), landing (bool).
 */
function page_open(array $o = [])
{
    $title = isset($o['title']) ? $o['title'] : 'Ecolife';
    $desc = isset($o['desc']) ? $o['desc'] : 'Ecolife — edukasi dan pemantauan kebiasaan konsumsi ramah lingkungan.';
    $body = isset($o['body']) ? $o['body'] : '';
    $GLOBALS['ECO_PAGE'] = $o;

    echo '<!DOCTYPE html><html lang="id" data-theme="dark"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
        . '<title>' . e($title) . '</title><meta name="description" content="' . e($desc) . '">'
        . '<meta name="theme-color" content="#06140f"><meta name="color-scheme" content="dark light">';
    if (!empty($o['csrf'])) {
        echo '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
    }
    echo '<link rel="icon" type="image/png" href="' . e(url('assets/img/favicon.png')) . '">'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">'
        . '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '">'
        . '<script src="' . e(asset('js/boot.js')) . '"></script></head>'
        . '<body class="' . e($body) . '">';
    echo '<a class="skip" href="#main">Lewati ke konten</a><div id="prog" aria-hidden="true"></div>';
    if (!array_key_exists('nav', $o) || $o['nav']) {
        render_nav(isset($o['active']) ? $o['active'] : '', !empty($o['landing']));
    }
}

function render_nav($active, $landing)
{
    $u = current_user();
    $home = $landing ? '' : url('index.php');
    $links = [
        ['beranda', 'Beranda', $home . '#beranda'],
        ['tentang', 'Tentang', $home . '#tentang'],
        ['alur', 'Alur', $home . '#alur'],
        ['model', 'Model', $home . '#model'],
        ['pantau', 'Pantau', url('pantau.php')],
        ['prediksi', 'Prediksi', url('prediksi.php')],
        ['tim', 'Tim', $home . '#tim'],
    ];
    echo '<header class="nav" id="nav"><div class="nav__inner glass">';
    echo '<a class="brand" href="' . e($home . '#beranda') . '"><img src="' . e(url('assets/img/logo.png')) . '" alt="" width="36" height="36">'
        . '<span>Eco<b>life</b></span></a>';
    echo '<nav class="nav__links" id="navlinks" aria-label="Navigasi utama">';
    foreach ($links as $l) {
        $cur = ($l[0] === $active) ? ' aria-current="page"' : '';
        echo '<a href="' . e($l[2]) . '" data-nav="' . e($l[0]) . '"' . $cur . '>' . e($l[1]) . '</a>';
    }
    if ($u) {
        echo '<a class="nav__only-mobile" href="' . e(url('dashboard.php')) . '"' . ($active === 'dashboard' ? ' aria-current="page"' : '') . '>Dashboard</a>';
    } else {
        echo '<a class="nav__only-mobile" href="' . e(url('login.php')) . '">Masuk</a>'
            . '<a class="nav__only-mobile" href="' . e(url('register.php')) . '">Daftar</a>';
    }
    echo '</nav><div class="nav__actions">';
    echo '<button type="button" id="theme" class="iconbtn" aria-label="Ganti tema terang atau gelap">' . ic('theme') . '</button>';
    if ($u) {
        echo '<a class="btn btn--lime btn--sm nav__cta" href="' . e(url('dashboard.php')) . '"><span class="btn__label">Dashboard</span></a>';
        $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($u['name'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($u['name'], 0, 1));
        echo '<div class="usermenu"><button type="button" class="usermenu__btn" aria-haspopup="true" aria-expanded="false" aria-controls="usermenu">'
            . '<span class="avatar" aria-hidden="true">' . e($initial) . '</span>'
            . '<span class="usermenu__name">' . e($u['name']) . '</span>' . ic('chevron', 16) . '</button>'
            . '<div class="usermenu__panel glass" id="usermenu" hidden>'
            . '<div class="usermenu__who"><b>' . e($u['name']) . '</b><small>' . e($u['email']) . '</small></div>'
            . '<a href="' . e(url('dashboard.php')) . '">' . ic('grid', 18) . 'Dashboard</a>'
            . '<a href="' . e(url('profil.php')) . '">' . ic('user', 18) . 'Profil</a>';
        if ($u['role'] === 'admin') {
            echo '<a href="' . e(url('admin/index.php')) . '">' . ic('shield', 18) . 'Admin</a>';
        }
        echo '<form method="post" action="' . e(url('logout.php')) . '">' . csrf_field()
            . '<button type="submit">' . ic('logout', 18) . 'Keluar</button></form></div></div>';
    } else {
        echo '<a class="btn btn--ghost btn--sm nav__login" href="' . e(url('login.php')) . '"><span class="btn__label">Masuk</span></a>'
            . '<a class="btn btn--lime btn--sm nav__cta" href="' . e(url('register.php')) . '"><span class="btn__label">Daftar</span>'
            . '<span class="btn__arrow">' . ic('arrow', 14) . '</span></a>';
    }
    echo '<button type="button" class="iconbtn nav__toggle" aria-expanded="false" aria-controls="navlinks" aria-label="Buka menu">' . ic('menu') . '</button>';
    echo '</div></div></header>';
}

function page_close(array $scripts = [], $footer = true)
{
    if ($footer && (!isset($GLOBALS['ECO_PAGE']['footer']) || $GLOBALS['ECO_PAGE']['footer'])) {
        echo '<footer class="footer"><div class="w footer__grid"><div><b class="footer__brand">Ecolife</b>'
            . '<p>Edukasi dan Pemantauan Kebiasaan Konsumsi Ramah Lingkungan</p></div>'
            . '<div><p>Universitas Halu Oleo, Fakultas Matematika dan Ilmu Pengetahuan Alam, Program Studi Ilmu Komputer</p>'
            . '<p class="footer__cp">© 2026 Ecolife. Dibangun sebagai proyek pembelajaran.</p></div></div></footer>';
    }
    echo '<button type="button" id="up" aria-label="Kembali ke atas">' . ic('up') . '</button>'
        . '<div id="toast" role="status" aria-live="polite"></div>';
    $flash = take_flash();
    if ($flash) {
        echo '<script type="application/json" id="flash-data">' . json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        echo '<noscript><div class="flash-noscript">';
        foreach ($flash as $f) { echo '<p>' . e($f['msg']) . '</p>'; }
        echo '</div></noscript>';
    }
    echo '<script src="' . e(asset('js/app.js')) . '" defer></script>';
    foreach ($scripts as $s) {
        echo '<script src="' . e(asset($s)) . '" defer></script>';
    }
    echo '</body></html>';
}

/** Data JSON untuk skrip halaman (aman terhadap injeksi </script>). */
function json_data_block($id, $data)
{
    echo '<script type="application/json" id="' . e($id) . '">'
        . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
}

function level_label_class($name)
{
    return $name === 'Hijau Inspiratif' ? 'lv-3' : ($name === 'Hijau Aktif' ? 'lv-2' : 'lv-1');
}
