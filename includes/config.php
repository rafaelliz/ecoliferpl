<?php
/**
 * Konfigurasi Ecolife.
 * Nilai bawaan cocok untuk XAMPP/Laragon (user root tanpa password).
 * Untuk mengubahnya, salin config.local.sample.php menjadi config.local.php
 * (atau gunakan environment variable ECOLIFE_DB_*). Jangan commit config.local.php.
 */
$env = function ($key, $default) {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

$config = [
    'db_host'  => $env('ECOLIFE_DB_HOST', '127.0.0.1'),
    'db_port'  => $env('ECOLIFE_DB_PORT', '3306'),
    'db_name'  => $env('ECOLIFE_DB_NAME', 'ecolife'),
    'db_user'  => $env('ECOLIFE_DB_USER', 'root'),
    'db_pass'  => $env('ECOLIFE_DB_PASS', ''),
    'timezone' => $env('ECOLIFE_TZ', 'Asia/Makassar'), // WITA (Kendari)
    'debug'    => $env('ECOLIFE_DEBUG', '') === '1',
    'session_lifetime' => 7200, // detik tidak aktif sebelum sesi berakhir
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $override = require $localFile;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}
return $config;
