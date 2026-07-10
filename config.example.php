<?php
// LiteBBS forum database configuration template.
// Copy this file to config.php and fill in your server database credentials.
define('DB_HOST', 'localhost');
define('DB_USER', 'pd_forum');
define('DB_PASS', 'database_password');
define('DB_NAME', 'pd_forum');
// define('DB_PORT', 3306); // 可选；SSH 隧道本地开发常用 3307
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'LiteBBS');
define('SITE_DESC', '简单干净，一目了然的轻量论坛');
define('POST_INTERVAL', 10);

// Web 安装/升级的一次性维护令牌。生产环境请使用至少 32 字节随机值；也可以只通过 CLI 运行安装脚本。
// define('PD_MAINTENANCE_TOKEN', 'replace-with-a-long-random-token');
// define('PD_PUBLIC_URL', 'https://forum.example.com'); // Passkey/OAuth/邮件链接使用的固定站点地址
// define('PD_PRIVATE_STORAGE_PATH', '/var/lib/php-do'); // 必须位于网站根目录之外；默认使用项目同级的 php-do-private
?>
