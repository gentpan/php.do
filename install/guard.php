<?php

function pd_require_maintenance_token($operation) {
    if (PHP_SAPI !== 'cli') {
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }
    if (PHP_SAPI === 'cli') {
        return;
    }

    $expected = defined('PD_MAINTENANCE_TOKEN') ? trim((string) PD_MAINTENANCE_TOKEN) : '';
    $provided = '';
    if (!empty($_SERVER['HTTP_X_MAINTENANCE_TOKEN'])) {
        $provided = trim((string) $_SERVER['HTTP_X_MAINTENANCE_TOKEN']);
    } elseif (isset($_GET['token'])) {
        $provided = trim((string) $_GET['token']);
    }

    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        header('Content-Type: text/plain; charset=utf-8', true, 403);
        exit('安装与升级入口已锁定。请在 config.php 配置 PD_MAINTENANCE_TOKEN，并通过 token 参数或 X-Maintenance-Token 请求头授权。');
    }

    if ($operation === 'install' && is_file(__DIR__ . '/../storage/install.lock')) {
        header('Content-Type: text/plain; charset=utf-8', true, 409);
        exit('程序已经安装。如需重新安装，请先备份数据并由服务器管理员移除 storage/install.lock。');
    }
}

function pd_write_install_lock() {
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
        return false;
    }
    return file_put_contents($dir . '/install.lock', gmdate(DATE_ATOM) . "\n", LOCK_EX) !== false;
}
