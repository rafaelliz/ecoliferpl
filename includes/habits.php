<?php
/** Katalog kebiasaan, level, dan status pencatatan (semua perhitungan poin di server). */

function habit_catalog()
{
    return [
        'tumbler' => ['emoji' => '🥤', 'label' => 'Membawa tumbler',              'points' => 10],
        'tas'     => ['emoji' => '🛍️', 'label' => 'Membawa tas belanja',          'points' => 10],
        'sedotan' => ['emoji' => '🚫', 'label' => 'Tidak memakai sedotan plastik', 'points' => 5],
        'reuse'   => ['emoji' => '🔁', 'label' => 'Menggunakan kembali barang',    'points' => 10],
        'pilah'   => ['emoji' => '🗑️', 'label' => 'Memilah sampah',                'points' => 10],
    ];
}

/** Level: Hijau Muda (<50), Hijau Aktif (50–149), Hijau Inspiratif (150+). */
function level_for($points)
{
    if ($points >= 150) { return ['name' => 'Hijau Inspiratif', 'next' => 0]; }
    if ($points >= 50)  { return ['name' => 'Hijau Aktif', 'next' => 150]; }
    return ['name' => 'Hijau Muda', 'next' => 50];
}

function pantau_state($uid)
{
    $pdo = db();
    $st = $pdo->prepare('SELECT COALESCE(SUM(total_points), 0) AS p, COUNT(DISTINCT entry_date) AS d FROM habit_entries WHERE user_id = ?');
    $st->execute([$uid]);
    $r = $st->fetch();
    $points = (int)$r['p'];
    $days = (int)$r['d'];

    $lv = level_for($points);
    $lv['percent'] = $lv['next'] ? min(100, $points / $lv['next'] * 100) : 100;
    $lv['remaining'] = $lv['next'] ? $lv['next'] - $points : 0;

    $st = $pdo->prepare('SELECT total_points, created_at FROM habit_entries WHERE user_id = ? ORDER BY id DESC LIMIT 10');
    $st->execute([$uid]);
    $history = [];
    foreach ($st->fetchAll() as $row) {
        $history[] = ['points' => (int)$row['total_points'], 'at' => iso($row['created_at'])];
    }

    $st = $pdo->prepare('SELECT habit_key FROM habit_entry_items WHERE user_id = ? AND entry_date = ?');
    $st->execute([$uid, today()]);
    $done = $st->fetchAll(PDO::FETCH_COLUMN);

    $catalog = [];
    foreach (habit_catalog() as $key => $h) {
        $catalog[] = ['key' => $key, 'emoji' => $h['emoji'], 'label' => $h['label'], 'points' => $h['points']];
    }

    return [
        'points'  => $points,
        'days'    => $days,
        'level'   => $lv,
        'history' => $history,
        'done'    => array_values($done),
        'catalog' => $catalog,
    ];
}

/**
 * Simpan kebiasaan hari ini. Poin dihitung dari katalog server dan tiap kebiasaan hanya
 * dihitung sekali per hari (dijaga transaksi + UNIQUE KEY), sehingga permintaan ganda tidak menambah poin.
 * Mengembalikan jumlah poin yang ditambahkan, atau 0 bila semua kebiasaan sudah tercatat.
 */
function save_habits($uid, array $keys)
{
    $catalog = habit_catalog();
    $pdo = db();
    $date = today();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE'); // serialisasi permintaan paralel
        $lock->execute([$uid]);
        $lock->fetchAll();

        $st = $pdo->prepare('SELECT habit_key FROM habit_entry_items WHERE user_id = ? AND entry_date = ?');
        $st->execute([$uid, $date]);
        $done = $st->fetchAll(PDO::FETCH_COLUMN);
        $fresh = array_values(array_diff($keys, $done));
        if (!$fresh) {
            $pdo->rollBack();
            return 0;
        }
        $total = 0;
        foreach ($fresh as $k) { $total += $catalog[$k]['points']; }

        $ins = $pdo->prepare('INSERT INTO habit_entries (user_id, entry_date, total_points, created_at) VALUES (?, ?, ?, ?)');
        $ins->execute([$uid, $date, $total, now()]);
        $entryId = (int)$pdo->lastInsertId();

        $item = $pdo->prepare('INSERT INTO habit_entry_items (entry_id, user_id, entry_date, habit_key, points) VALUES (?, ?, ?, ?, ?)');
        foreach ($fresh as $k) {
            $item->execute([$entryId, $uid, $date, $k, $catalog[$k]['points']]);
        }
        $pdo->commit();
        return $total;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

function reset_habits($uid)
{
    $st = db()->prepare('DELETE FROM habit_entries WHERE user_id = ?'); // rincian ikut terhapus (ON DELETE CASCADE)
    $st->execute([$uid]);
}
