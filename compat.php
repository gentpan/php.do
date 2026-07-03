<?php
if (PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80600) {
    header('Content-Type: text/html; charset=utf-8');
    exit('当前程序配置为兼容 PHP 8.4-8.5，请使用 PHP 8.4 或 PHP 8.5 运行。');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (function_exists('date_default_timezone_set') && !ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Shanghai');
}
?>
