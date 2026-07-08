<?php
function pd_environment_error($message) {
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    exit($message);
}

if (PHP_VERSION_ID < 80500) {
    pd_environment_error('php.do 仅支持 PHP 8.5+，请升级 PHP 运行环境。');
}

if (!extension_loaded('json')) {
    pd_environment_error('php.do 需要开启 PHP json 扩展。');
}

if (!extension_loaded('mysqli')) {
    pd_environment_error('php.do 需要开启 PHP mysqli 扩展。');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

function pd_mysql_version_supported($server_info) {
    $server_info = trim((string)$server_info);
    if ($server_info === '' || stripos($server_info, 'mariadb') !== false) {
        return false;
    }
    if (!preg_match('/^([0-9]+)\.([0-9]+)/', $server_info, $m)) {
        return false;
    }
    $major = intval($m[1]);
    return $major === 8 || $major === 9;
}

function pd_assert_mysql_runtime($conn) {
    $server_info = mysqli_get_server_info($conn);
    if (!pd_mysql_version_supported($server_info)) {
        pd_environment_error('php.do 仅支持 MySQL 8.x 或 MySQL 9.x beta，当前数据库版本：' . $server_info);
    }
}

// 应用逻辑与数据库会话统一为 UTC；展示层按用户时区转换
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('UTC');
}
?>
