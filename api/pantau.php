<?php
define('ECOLIFE_API', true);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/habits.php';

$user = api_guard(['GET', 'POST']);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['ok' => true, 'state' => pantau_state($uid)]);
}

$body = read_json_body();
$action = isset($body['action']) ? $body['action'] : '';

if ($action === 'save') {
    $catalog = habit_catalog();
    $keys = [];
    if (isset($body['habits']) && is_array($body['habits'])) {
        foreach ($body['habits'] as $k) {
            if (!is_string($k) || !isset($catalog[$k])) { json_error(422, 'Kebiasaan tidak dikenal.'); }
            $keys[$k] = $k;
        }
    }
    $keys = array_values($keys);
    if (!$keys) { json_error(422, 'Centang minimal satu kebiasaan.'); }

    $added = save_habits($uid, $keys);
    if ($added === 0) {
        json_error(409, 'Kebiasaan yang kamu pilih sudah tercatat hari ini.', ['state' => pantau_state($uid)]);
    }
    json_out(['ok' => true, 'added' => $added, 'state' => pantau_state($uid)]);
}

if ($action === 'reset') {
    reset_habits($uid);
    json_out(['ok' => true, 'state' => pantau_state($uid)]);
}

json_error(400, 'Permintaan tidak dikenal.');
