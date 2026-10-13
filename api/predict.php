<?php
define('ECOLIFE_API', true);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/predict.php';

$user = api_guard(['GET', 'POST']);
$uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['ok' => true, 'history' => prediction_history($uid)]);
}

$body = read_json_body();
list($v, $err) = eco_build_input(isset($body['values']) ? $body['values'] : null);
if ($err !== null) { json_error(422, $err); }

$result = eco_predict($v);
save_prediction($uid, $result);
$top = eco_top($result);

json_out(['ok' => true, 'top' => $top['c'], 'result' => $result, 'history' => prediction_history($uid)]);
