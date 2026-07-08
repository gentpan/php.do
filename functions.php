<?php
if (!defined('PD_START')) {
    define('PD_START', microtime(true));
}
// 项目根目录：core/ 下的函数用 PD_ROOT 定位根级文件（不能再用 __DIR__，其值会是 core/）
if (!defined('PD_ROOT')) {
    define('PD_ROOT', __DIR__);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/compat.php';
if (PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    // 使用应用自有 session 目录，避免系统 /var/lib/php/sessions 无读权限导致 GC opendir 失败
    $pd_session_dir = __DIR__ . '/storage/sessions';
    if (!is_dir($pd_session_dir)) {
        @mkdir($pd_session_dir, 0775, true);
    }
    if (is_dir($pd_session_dir) && is_writable($pd_session_dir)) {
        session_save_path($pd_session_dir);
    }
}
session_start();
if (!empty($_SESSION['qf_uid']) && empty($_SESSION['pd_uid'])) {
    $_SESSION['pd_uid'] = intval($_SESSION['qf_uid']);
    unset($_SESSION['qf_uid']);
}

// ===== 函数模块（仅定义，无副作用；见 core/）=====
require_once __DIR__ . '/core/content.php';
require_once __DIR__ . '/core/geoip.php';
require_once __DIR__ . '/core/mail.php';
require_once __DIR__ . '/core/media.php';
require_once __DIR__ . '/core/messaging.php';
require_once __DIR__ . '/core/render.php';
require_once __DIR__ . '/core/schema.php';
require_once __DIR__ . '/core/security.php';
require_once __DIR__ . '/core/settings.php';
require_once __DIR__ . '/core/url.php';
require_once __DIR__ . '/core/user.php';
require_once __DIR__ . '/core/util.php';

// ===== 运行时末尾守卫 =====
if (PHP_SAPI !== 'cli') {
    pd_migrate_schema_prefix_from_qf();
    ob_start('pd_inject_csrf_fields');
    pd_ensure_upload_protection();
    pd_require_csrf();
}
pd_security_guard();
?>
