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
$from_cache = 0;
foreach ($ips as $ip) {
  // 先试缓存读：用于统计；真正 lookup 仍会走 mem/disk，不会重复打 API
  $hit = qf_geoip_cache_read($ip);
  if (is_array($hit)) {
    $from_cache++;
    $data[$ip] = $hit;
  } else {
    $data[$ip] = qf_geoip_lookup($ip);
  }
}

// 浏览器也可缓存结果，减少重复进管理页的二次请求
header('Cache-Control: private, max-age=3600');
qf_json_response(array(
  'ok' => 1,
  'data' => $data,
  'cache' => array(
    'hits' => $from_cache,
    'total' => count($ips),
  ),
));
