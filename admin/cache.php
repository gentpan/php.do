<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$messages = array();

if (function_exists('opcache_reset')) {
    if (@opcache_reset()) {
        $messages[] = 'OPcache 已清理。';
    } else {
        $messages[] = 'OPcache 无需清理或当前环境不支持重置。';
    }
} else {
    $messages[] = '当前 PHP 环境未开启 OPcache。';
}

unset($_SESSION['flash']);
$messages[] = '会话提示缓存已清理。';
$messages[] = '缓存已清理。';

$page_title = '清理缓存 - ' . SITE_NAME;
qf_include_admin_header();
?>
<section class="card">
    <div class="admin-page-title">
        <h1>清理缓存</h1>
    </div>
<?php foreach ($messages as $msg) { ?>
        <p class="success"><?php echo h($msg); ?></p>
    <?php } ?>
</section>
<?php qf_include_admin_footer(); ?>
