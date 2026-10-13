<?php
// Salin file ini menjadi config.local.php lalu sesuaikan.
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'ecolife',
    'db_user' => 'ecolife_app',   // disarankan: user khusus, bukan root
    'db_pass' => 'ganti-dengan-password-kuat',
    'timezone' => 'Asia/Makassar',
    'debug' => false,             // true hanya saat development
];
