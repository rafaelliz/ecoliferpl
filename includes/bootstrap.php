<?php
/**
 * Ecolife — bootstrap: konfigurasi, keamanan, sesi, CSRF, helper umum.
 * Kompatibel PHP 7.4+.
 */
if (defined('ECOLIFE_BOOT')) { return; }
define('ECOLIFE_BOOT', true);
define('ROOT_DIR', str_replace('\\', '/', dirname(__DIR__)));
define('SESSION_NAME', 'ecolife_sid');

$GLOBALS['ECO_CONFIG'] = require __DIR__ . '/config.php';

function cfg($key, $default = null)
{
    return array_key_exists($key, $GLOBALS['ECO_CONFIG']) ? $GLOBALS['ECO_CONFIG'][$key] : $default;
}

date_default_timezone_set(cfg('timezone', 'Asia/Makassar'));
ini_set('display_errors', cfg('debug') ? '1' : '0');
error_reporting(E_ALL);

/* Awalan relatif ke root proyek ('' untuk halaman di root, '../' untuk admin/ dan api/). */
$__script = isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : '';
if ($__script !== '' && realpath($__script)) {
    $__script = realpath($__script);
}
$__script = str_replace('\\', '/', $__script);
define('BASE', ($__script === '' || strcasecmp(rtrim(dirname($__script), '/'), ROOT_DIR) === 0) ? '' : '../');
unset($__script);

/* ---------- Helper umum ---------- */

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url($path = '')
{
    return BASE . $path;
}

function asset($path)
{
    $f = ROOT_DIR . '/assets/' . $path;
    $v = is_file($f) ? filemtime($f) : 1;
    return BASE . 'assets/' . $path . '?v=' . $v;
}

function redirect($path)
{
    header('Location: ' . BASE . $path);
    exit;
}

function now()
{
    return date('Y-m-d H:i:s');
}

function today()
{
    return date('Y-m-d');
}

function iso($datetime)
{
    try {
        $d = new DateTime($datetime);
        return $d->format(DATE_ATOM);
    } catch (Exception $e) {
        return null;
    }
}

function ulen($s)
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function client_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : '0.0.0.0';
}

/* ---------- Header keamanan ---------- */

function send_security_headers()
{
    if (headers_sent()) { return; }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; "
        . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
    // Halaman dinamis tidak boleh disimpan cache (tombol Back setelah logout tidak menampilkan halaman terlindungi).
    header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

if (PHP_SAPI !== 'cli') {
    send_security_headers();
}

/* ---------- Database ---------- */

function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . cfg('db_host') . ';port=' . cfg('db_port')
            . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/* ---------- Penanganan error ---------- */

function json_out($data, $code = 200)
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

function json_error($code, $message, $extra = [])
{
    json_out(array_merge(['ok' => false, 'error' => $message], $extra), $code);
}

function error_page($code, $title, $message)
{
    if (!headers_sent()) { http_response_code($code); }
    echo '<!DOCTYPE html><html lang="id" data-theme="dark"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($title) . ' · Ecolife</title>'
        . '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '"></head>'
        . '<body class="page-error"><main class="errbox glass"><h1>' . e($title) . '</h1><p>' . e($message) . '</p>'
        . '<p><a class="btn btn--lime" href="' . e(url('index.php')) . '"><span class="btn__label">Kembali ke beranda</span></a></p>'
        . '</main></body></html>';
    exit;
}

set_exception_handler(function ($e) {
    error_log('[Ecolife] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $hint = 'Terjadi kesalahan pada server. Silakan coba lagi.';
    if ($e instanceof PDOException) {
        $c = (string)$e->getCode();
        if (in_array($c, ['1044', '1045', '1049', '2002', '2003', '2006'], true)) {
            $hint = 'Tidak dapat terhubung ke database. Pastikan MySQL berjalan dan periksa pengaturan di includes/config.local.php.';
        } elseif ($c === '42S02' || $c === '42S22') {
            $hint = 'Tabel database belum lengkap. Impor ulang database/schema.sql.';
        }
    }
    if (cfg('debug')) { $hint .= ' [' . $e->getMessage() . ']'; }
    if (defined('ECOLIFE_API')) { json_error(500, $hint); }
    error_page(500, 'Terjadi kesalahan', $hint);
});

/* ---------- Sesi ---------- */

function start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function destroy_session()
{
    start_session();
    $_SESSION = [];
    if (!headers_sent()) {
        $p = session_get_cookie_params();
        setcookie(SESSION_NAME, '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function login_user($id)
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid']  = (int)$id;
    $_SESSION['last'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    current_user(true);
}

/** Pengguna yang sedang login (dibaca ulang dari DB di setiap request, sehingga role selalu terbaru). */
function current_user($refresh = false)
{
    static $cache = null;
    if ($cache !== null && !$refresh) { return $cache['u']; }
    $cache = ['u' => null];
    if (!isset($_COOKIE[SESSION_NAME])) { return null; }
    start_session();
    if (empty($_SESSION['uid'])) { return null; }
    if (isset($_SESSION['last']) && time() - (int)$_SESSION['last'] > (int)cfg('session_lifetime', 7200)) {
        destroy_session();
        return null;
    }
    $_SESSION['last'] = time();
    $st = db()->prepare('SELECT id, name, email, role, created_at, last_login_at FROM users WHERE id = ?');
    $st->execute([(int)$_SESSION['uid']]);
    $row = $st->fetch();
    if (!$row) {
        destroy_session();
        return null;
    }
    $cache['u'] = $row;
    return $row;
}

function is_admin($user = null)
{
    $user = $user ?: current_user();
    return $user && $user['role'] === 'admin';
}

/* ---------- Flash message ---------- */

function flash($type, $message)
{
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flash()
{
    if (!isset($_COOKIE[SESSION_NAME]) && session_status() !== PHP_SESSION_ACTIVE) { return []; }
    start_session();
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token()
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    start_session();
    $t = isset($_POST['csrf']) ? $_POST['csrf'] : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

/* ---------- Guard akses ---------- */

/** Tujuan "next" yang diizinkan (whitelist, mencegah open redirect). */
function next_targets()
{
    return [
        'dashboard' => 'dashboard.php',
        'pantau'    => 'pantau.php',
        'prediksi'  => 'prediksi.php',
        'profil'    => 'profil.php',
        'admin'     => 'admin/index.php',
    ];
}

function sanitize_next($key)
{
    $key = is_string($key) ? $key : '';
    return array_key_exists($key, next_targets()) ? $key : '';
}

/** Wajib login. Pengunjung diarahkan ke halaman login dan kembali ke halaman tujuan setelah masuk. */
function require_login($nextKey = '')
{
    $u = current_user();
    if ($u) { return $u; }
    $msgs = [
        'pantau'   => 'Masuk dulu untuk memakai fitur Pantau kebiasaan.',
        'prediksi' => 'Masuk dulu untuk memakai fitur Prediksi.',
    ];
    flash('info', isset($msgs[$nextKey]) ? $msgs[$nextKey] : 'Silakan masuk untuk melanjutkan.');
    redirect('login.php' . ($nextKey !== '' ? '?next=' . urlencode($nextKey) : ''));
}

/** Wajib admin — role dibaca dari database, bukan dari sesi atau input pengguna. */
function require_admin()
{
    $u = require_login('admin');
    if ($u['role'] !== 'admin') {
        error_page(403, 'Akses ditolak', 'Halaman ini hanya untuk admin Ecolife.');
    }
    return $u;
}

/** Pembuka endpoint API: metode, login, dan CSRF. Mengembalikan data pengguna. */
function api_guard(array $methods)
{
    header('Content-Type: application/json; charset=utf-8');
    $m = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    if (!in_array($m, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        json_error(405, 'Metode tidak diizinkan.');
    }
    $u = current_user();
    if (!$u) { json_error(401, 'Sesi berakhir. Silakan masuk kembali.', ['login' => true]); }
    if ($m !== 'GET' && !csrf_check()) {
        json_error(403, 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
    return $u;
}

function read_json_body()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 20000) { json_error(413, 'Permintaan terlalu besar.'); }
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}
