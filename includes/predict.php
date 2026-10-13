<?php
/**
 * Prediksi tingkat kebiasaan ramah lingkungan — port PHP dari model Logistic Regression
 * yang sebelumnya berjalan di browser (bobot di includes/model.json, tidak diubah).
 * Dijalankan di server agar hanya pengguna yang login yang dapat memakainya.
 */

const ECO_KURS = 16000;          // Rp per USD (sama dengan versi lama)
const ECO_WK = 52 / 12;          // minggu → bulan (sama dengan versi lama)

function eco_model()
{
    static $m = null;
    if ($m === null) { $m = json_decode(file_get_contents(__DIR__ . '/model.json'), true); }
    return $m;
}

/** Spesifikasi form: label, opsi, nilai awal, batas. Dipakai server (validasi) dan klien (tampilan). */
function eco_form_spec()
{
    return [
        'selects' => [
            'Diet' => ['label' => 'Pola makan', 'default' => 'omnivore', 'options' => [
                'omnivore' => 'Pemakan daging & sayur', 'pescatarian' => 'Pescatarian', 'vegetarian' => 'Vegetarian', 'vegan' => 'Vegan']],
            'Transport' => ['label' => 'Transportasi utama', 'default' => 'public', 'options' => [
                'private' => 'Kendaraan pribadi', 'public' => 'Transportasi umum', 'walk/bicycle' => 'Jalan kaki / sepeda']],
            'Vehicle Type' => ['label' => 'Jenis kendaraan', 'default' => 'none', 'options' => [
                'none' => 'Tidak punya', 'petrol' => 'Bensin', 'diesel' => 'Diesel', 'electric' => 'Listrik']],
            'Frequency of Traveling by Air' => ['label' => 'Naik pesawat', 'default' => 'rarely', 'options' => [
                'never' => 'Tidak pernah', 'rarely' => 'Jarang', 'frequently' => 'Sering', 'very frequently' => 'Sangat sering']],
            'Waste Bag Size' => ['label' => 'Ukuran kantong sampah', 'default' => 'medium', 'options' => [
                'small' => 'Kecil', 'medium' => 'Sedang', 'large' => 'Besar', 'extra large' => 'Sangat besar']],
            'Energy efficiency' => ['label' => 'Peduli alat hemat energi?', 'default' => 'Sometimes', 'options' => [
                'Yes' => 'Ya', 'Sometimes' => 'Kadang', 'No' => 'Tidak']],
            // Nilai model adalah "natural gas" (versi lama mengirim "gas" sehingga tidak pernah cocok).
            'Heating Energy Source' => ['label' => 'Sumber energi pemanas/memasak', 'default' => 'electricity', 'options' => [
                'electricity' => 'Listrik', 'wood' => 'Kayu', 'natural gas' => 'Gas', 'coal' => 'Arang']],
        ],
        'numbers' => [
            'Monthly Grocery Bill'        => ['label' => 'Belanja bulanan (Rp)',          'default' => 0, 'min' => 0, 'max' => 8000000],
            'Vehicle Monthly Distance Km' => ['label' => 'Jarak kendaraan per minggu (km)', 'default' => 0, 'min' => 0, 'max' => 2500],
            'Waste Bag Weekly Count'      => ['label' => 'Kantong sampah per minggu',      'default' => 0, 'min' => 0, 'max' => 10],
            'How Many New Clothes Monthly' => ['label' => 'Pakaian baru per minggu',       'default' => 0, 'min' => 0, 'max' => 12],
        ],
        'recycle' => ['Glass' => 'Kaca', 'Metal' => 'Logam', 'Paper' => 'Kertas', 'Plastic' => 'Plastik'],
    ];
}

/** Nilai tetap untuk fitur yang tidak ditampilkan di form (sama dengan versi lama). */
function eco_defaults()
{
    return [
        'Body Type' => 'normal', 'Sex' => 'female', 'How Often Shower' => 'daily', 'Social Activity' => 'sometimes',
        'How Long TV PC Daily Hour' => 8, 'How Long Internet Daily Hour' => 6,
        'Cook_Airfryer' => 0, 'Cook_Grill' => 0, 'Cook_Microwave' => 0, 'Cook_Oven' => 0, 'Cook_Stove' => 0,
    ];
}

/** Validasi input mentah dari form. Mengembalikan [vektor fitur, pesan error|null]. */
function eco_build_input($raw)
{
    if (!is_array($raw)) { return [null, 'Data isian tidak valid.']; }
    $spec = eco_form_spec();
    $v = [];
    foreach ($spec['selects'] as $key => $s) {
        $val = isset($raw[$key]) && is_string($raw[$key]) ? $raw[$key] : $s['default'];
        if (!array_key_exists($val, $s['options'])) { return [null, 'Pilihan "' . $s['label'] . '" tidak valid.']; }
        $v[$key] = $val;
    }
    foreach ($spec['numbers'] as $key => $n) {
        $val = isset($raw[$key]) ? $raw[$key] : $n['default'];
        if ($val === '' || $val === null) { $val = 0; }
        if (!is_numeric($val)) { return [null, '"' . $n['label'] . '" harus berupa angka.']; }
        $val = (float)$val;
        if ($val < $n['min'] || $val > $n['max']) {
            return [null, '"' . $n['label'] . '" harus antara ' . $n['min'] . ' dan ' . $n['max'] . '.'];
        }
        $v[$key] = $val;
    }
    foreach ($spec['recycle'] as $k => $_label) {
        $v['Recycle_' . $k] = (!empty($raw['Recycle_' . $k]) && $raw['Recycle_' . $k] !== '0') ? 1 : 0;
    }
    // Konversi satuan persis seperti versi lama
    $v['Monthly Grocery Bill'] = $v['Monthly Grocery Bill'] / ECO_KURS;
    $v['Vehicle Monthly Distance Km'] = $v['Vehicle Monthly Distance Km'] * ECO_WK;
    $v['How Many New Clothes Monthly'] = $v['How Many New Clothes Monthly'] * ECO_WK;
    foreach (eco_defaults() as $k => $d) {
        if (!array_key_exists($k, $v)) { $v[$k] = $d; }
    }
    return [$v, null];
}

/** Hasil: daftar [kelas, peluang 0..1] sesuai urutan model (Rendah, Sedang, Tinggi). */
function eco_predict(array $v)
{
    $M = eco_model();
    $rc = 0;
    foreach (['Glass', 'Metal', 'Paper', 'Plastic'] as $k) { $rc += (float)(isset($v['Recycle_' . $k]) ? $v['Recycle_' . $k] : 0); }
    $v['Recycle_Count'] = $rc;
    $cc = 0;
    foreach (['Airfryer', 'Grill', 'Microwave', 'Oven', 'Stove'] as $k) { $cc += (float)(isset($v['Cook_' . $k]) ? $v['Cook_' . $k] : 0); }
    $v['Cook_Count'] = $cc;

    $f = [];
    foreach ($M['num'] as $i => $name) {
        $f[] = ((float)$v[$name] - $M['mu'][$i]) / $M['sd'][$i];
    }
    foreach ($M['cat'] as $col => $opts) {
        foreach ($opts as $o) { $f[] = ((string)$v[$col] === (string)$o) ? 1 : 0; }
    }
    $scores = [];
    foreach ($M['W'] as $k => $w) {
        $a = $M['b'][$k];
        foreach ($w as $i => $x) { $a += $x * $f[$i]; }
        $scores[] = $a;
    }
    $mx = max($scores);
    $e = [];
    $t = 0.0;
    foreach ($scores as $s) { $ex = exp($s - $mx); $e[] = $ex; $t += $ex; }
    $out = [];
    foreach ($M['cls'] as $i => $c) { $out[] = ['c' => $c, 'p' => $e[$i] / $t]; }
    return $out;
}

function eco_top(array $result)
{
    $top = $result[0];
    foreach ($result as $r) { if ($r['p'] > $top['p']) { $top = $r; } }
    return $top;
}

function eco_pct(array $result, $cls)
{
    foreach ($result as $r) { if ($r['c'] === $cls) { return round($r['p'] * 100, 1); } }
    return 0.0;
}

function save_prediction($uid, array $result)
{
    $pdo = db();
    $top = eco_top($result);
    $st = $pdo->prepare('INSERT INTO predictions (user_id, result_class, p_tinggi, p_sedang, p_rendah, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $st->execute([$uid, $top['c'], eco_pct($result, 'Tinggi'), eco_pct($result, 'Sedang'), eco_pct($result, 'Rendah'), now()]);
    // simpan maksimal 50 hasil terbaru per pengguna
    $q = $pdo->prepare('SELECT id FROM predictions WHERE user_id = ? ORDER BY id DESC LIMIT 1 OFFSET 49');
    $q->execute([$uid]);
    $cut = $q->fetchColumn();
    if ($cut) {
        $d = $pdo->prepare('DELETE FROM predictions WHERE user_id = ? AND id < ?');
        $d->execute([$uid, $cut]);
    }
}

/** Riwayat (maks. 10 terbaru) dalam format yang sama dengan versi lama. */
function prediction_history($uid, $limit = 10)
{
    $st = db()->prepare('SELECT result_class, p_tinggi, p_sedang, p_rendah, created_at FROM predictions WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit);
    $st->execute([$uid]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[] = [
            'c' => $r['result_class'],
            'Tinggi' => number_format((float)$r['p_tinggi'], 1, '.', ''),
            'Sedang' => number_format((float)$r['p_sedang'], 1, '.', ''),
            'Rendah' => number_format((float)$r['p_rendah'], 1, '.', ''),
            't' => iso($r['created_at']),
        ];
    }
    return $out;
}
