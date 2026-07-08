<?php
/**
 * 把种子数据里的私网 IP 替换成可被 cnip.io 解析的公网示例 IP（便于管理员看到国旗）。
 * 用法：php scripts/fix-private-ips.php
 * 已存在的真实公网 IP 不会被覆盖。
 */
require_once __DIR__ . '/../functions.php';

// 各国常见公共 DNS / CDN 出口，仅用于演示展示国旗
$public_ips = array(
    '8.8.8.8',          // US Google
    '1.1.1.1',          // AU Cloudflare
    '208.67.222.222',   // US OpenDNS
    '9.9.9.9',          // US Quad9
    '223.5.5.5',        // CN AliDNS
    '180.76.76.76',     // CN Baidu
    '114.114.114.114',  // CN 114DNS
    '119.29.29.29',     // CN DNSPod
    '101.226.4.6',      // CN
    '210.140.92.187',   // JP
    '210.130.1.1',      // JP
    '168.126.63.1',     // KR
    '168.95.1.1',       // TW
    '202.45.84.58',     // HK
    '165.21.83.88',     // SG
    '61.19.253.169',    // TH
    '203.80.96.10',     // VN-ish / APAC
    '203.112.2.5',      // BD
    '49.207.36.89',     // IN
    '185.228.168.9',    // EU CleanBrowsing
    '77.88.8.8',        // RU Yandex
    '213.186.33.99',    // FR OVH
    '151.101.1.69',     // US Fastly
    '104.16.132.229',   // US Cloudflare
    '142.250.190.78',   // US Google
    '13.107.42.14',     // US Microsoft
    '213.230.114.118',  // UZ（站内已有真实样本）
);

function qf_fix_pick_public_ip(array $pool, $seed) {
    $n = count($pool);
    if ($n < 1) {
        return '8.8.8.8';
    }
    return $pool[abs(crc32((string)$seed)) % $n];
}

$tables = array(
    'qf_threads' => 'id',
    'qf_posts' => 'id',
    'qf_post_comments' => 'id',
    'qf_users' => 'id',
);

$summary = array();
foreach ($tables as $table => $id_col) {
    $exists = mysqli_query(db(), "SHOW TABLES LIKE '" . esc($table) . "'");
    if (!$exists || mysqli_num_rows($exists) === 0) {
        continue;
    }
    $has_ip = mysqli_query(db(), "SHOW COLUMNS FROM `{$table}` LIKE 'ip'");
    if (!$has_ip || mysqli_num_rows($has_ip) === 0) {
        continue;
    }
    $updated = 0;
    $rs = mysqli_query(db(), "SELECT `{$id_col}` AS rid, ip FROM `{$table}`");
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $old = isset($row['ip']) ? trim((string)$row['ip']) : '';
        if ($old !== '' && !qf_ip_is_private_or_local($old)) {
            continue;
        }
        $new_ip = esc(qf_fix_pick_public_ip($public_ips, $table . ':' . $row['rid']));
        if (mysqli_query(db(), "UPDATE `{$table}` SET ip='{$new_ip}' WHERE `{$id_col}`=" . intval($row['rid']))) {
            $updated++;
        }
    }
    $summary[$table] = $updated;
}

if (PHP_SAPI === 'cli') {
    foreach ($summary as $table => $count) {
        echo $table . ': updated ' . $count . "\n";
    }
    echo "done\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    foreach ($summary as $table => $count) {
        echo $table . ': updated ' . $count . "\n";
    }
    echo "done\n";
}
