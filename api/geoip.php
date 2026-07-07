<?php
require_once __DIR__ . '/../functions.php';
if (!is_admin()) {
    qf_json_response(array('ok' => 0, 'error' => '无权限'), 403);
}

$ips = array();
if (!empty($_GET['ips'])) {
  foreach (explode(',', (string)$_GET['ips']) as $raw) {
    $raw = trim($raw);
    if ($raw !== '' && filter_var($raw, FILTER_VALIDATE_IP)) {
      $ips[] = $raw;
    }
  }
} elseif (!empty($_GET['ip']) && filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
  $ips[] = trim((string)$_GET['ip']);
}

$ips = array_values(array_unique($ips));
if (count($ips) > 30) {
  $ips = array_slice($ips, 0, 30);
}
if (empty($ips)) {
  qf_json_response(array('ok' => 0, 'error' => 'IP 无效'), 400);
}

$data = array();
foreach ($ips as $ip) {
  $data[$ip] = qf_geoip_lookup($ip);
}

qf_json_response(array('ok' => 1, 'data' => $data));
