<?php
/**
 * Membuat akun admin dengan aman — hanya bisa dijalankan dari command line.
 *
 *   php database/create_admin.php                       (membuat admin baru, interaktif)
 *   php database/create_admin.php --promote email@x.id  (menjadikan akun yang sudah terdaftar sebagai admin)
 *
 * Tidak ada akun/password bawaan. Password dimasukkan saat dijalankan dan disimpan sebagai hash.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';

function ask($label, $hidden = false)
{
    echo $label;
    $win = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($hidden && !$win) { shell_exec('stty -echo'); }
    $v = rtrim((string)fgets(STDIN), "\r\n");
    if ($hidden && !$win) { shell_exec('stty echo'); echo "\n"; }
    return $v;
}

if (isset($argv[1]) && $argv[1] === '--promote') {
    $email = normalize_email(isset($argv[2]) ? $argv[2] : ask('Email akun yang akan dijadikan admin: '));
    $st = db()->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
    $st->execute([$email]);
    echo $st->rowCount() ? "Berhasil: $email sekarang admin.\n" : "Tidak ada perubahan (email tidak ditemukan atau sudah admin).\n";
    exit(0);
}

list($name, $err) = validate_name(ask('Nama admin: '));
if ($err) { fwrite(STDERR, "$err\n"); exit(1); }
$email = normalize_email(ask('Email admin: '));
if ($err = validate_email_addr($email)) { fwrite(STDERR, "$err\n"); exit(1); }
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') { echo "(Catatan: di Windows, password terlihat saat diketik.)\n"; }
$pw = ask('Password (min. 8 karakter, huruf + angka): ', true);
if ($err = validate_password($pw)) { fwrite(STDERR, "$err\n"); exit(1); }
if ($pw !== ask('Ulangi password: ', true)) { fwrite(STDERR, "Password tidak sama.\n"); exit(1); }

$id = create_user($name, $email, $pw);
if ($id === false) { fwrite(STDERR, "Email sudah terdaftar. Gunakan: php database/create_admin.php --promote $email\n"); exit(1); }
$st = db()->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
$st->execute([$id]);
echo "Admin '$email' berhasil dibuat.\n";
