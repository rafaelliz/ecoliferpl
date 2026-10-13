<?php
/** Fungsi autentikasi: validasi, pembuatan akun, pembatasan percobaan login. */

function normalize_email($s)
{
    return strtolower(trim((string)$s));
}

function validate_name($name)
{
    $name = trim(preg_replace('/\s+/u', ' ', (string)$name));
    if (ulen($name) < 2 || ulen($name) > 60) {
        return [$name, 'Nama harus 2–60 karakter.'];
    }
    if (!preg_match('/^[\p{L}\p{M}0-9 .\'’-]+$/u', $name)) {
        return [$name, "Nama hanya boleh berisi huruf, angka, spasi, dan tanda . ' -"];
    }
    return [$name, null];
}

function validate_email_addr($email)
{
    if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Masukkan alamat email yang valid.';
    }
    return null;
}

function validate_password($pw)
{
    if (ulen($pw) < 8) { return 'Password minimal 8 karakter.'; }
    if (strlen($pw) > 72) { return 'Password terlalu panjang (maksimal 72 byte).'; }
    if (!preg_match('/\pL/u', $pw) || !preg_match('/\d/', $pw)) {
        return 'Password harus mengandung huruf dan angka.';
    }
    return null;
}

/** Buat akun baru (role selalu "user"). Mengembalikan id, atau false bila email sudah terdaftar. */
function create_user($name, $email, $password)
{
    try {
        $st = db()->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user', now()]);
        return (int)db()->lastInsertId();
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) { return false; }
        throw $e;
    }
}

function login_throttled($email)
{
    $since = date('Y-m-d H:i:s', time() - 900);
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?');
    $st->execute([$email, $since]);
    if ((int)$st->fetchColumn() >= 5) { return true; }
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?');
    $st->execute([client_ip(), $since]);
    return (int)$st->fetchColumn() >= 20;
}

function record_login_failure($email)
{
    $st = db()->prepare('INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?, ?, ?)');
    $st->execute([client_ip(), substr($email, 0, 150), now()]);
    $st = db()->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
    $st->execute([date('Y-m-d H:i:s', time() - 86400)]);
}

function clear_login_failures($email)
{
    $st = db()->prepare('DELETE FROM login_attempts WHERE email = ?');
    $st->execute([$email]);
}

/** Verifikasi kredensial. Mengembalikan id pengguna atau null. Waktu proses dibuat mirip bila email tidak ada. */
function verify_credentials($email, $password)
{
    $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $row = $st->fetch();
    $hash = $row ? $row['password_hash'] : password_hash('dummy-' . $email, PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash);
    if (!$row || !$ok) { return null; }
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $up->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    return (int)$row['id'];
}

function touch_last_login($id)
{
    $st = db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
    $st->execute([now(), $id]);
}
