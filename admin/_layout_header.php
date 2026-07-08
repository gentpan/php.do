<?php
if (!isset($page_title)) {
    $page_title = '后台管理 - ' . SITE_NAME;
}
$admin_me = current_user();
$admin_nav = qf_admin_nav_items();
$admin_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php');
$admin_css = 'assets/admin.css';
$admin_css_ver = file_exists(__DIR__ . '/../' . $admin_css) ? filemtime(__DIR__ . '/../' . $admin_css) : time();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($page_title); ?></title>
    <base href="<?php echo h(qf_base_href()); ?>">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://static.bluecdn.com/libs/fontawesome/7.3.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo h($admin_css); ?>?v=<?php echo intval($admin_css_ver); ?>">
    <script>window.qfCsrfToken = <?php echo json_encode(qf_csrf_token()); ?>;</script>
    <script defer src="assets/lib/alpine.min.js"></script>
</head>
<body class="admin-app" data-admin-page="<?php echo h(preg_replace('/\.php$/', '', $admin_script)); ?>">
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-brand">
            <a href="<?php echo h(qf_url_page('admin/index.php')); ?>">
                <span class="admin-brand-mark"><?php echo h(qf_site_name()); ?></span>
                <span class="admin-brand-sub">Admin</span>
            </a>
        </div>
        <nav class="admin-nav" aria-label="后台导航">
            <?php foreach ($admin_nav as $group) { ?>
                <div class="admin-nav-group">
                    <div class="admin-nav-label"><?php echo h($group['label']); ?></div>
                    <?php foreach ($group['items'] as $item) {
                        $active = ($admin_script === $item['script']);
                        ?>
                        <a class="admin-nav-link<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo h(qf_url_page('admin/' . $item['script'])); ?>">
                            <i class="<?php echo h($item['icon']); ?>" aria-hidden="true"></i>
                            <span><?php echo h($item['title']); ?></span>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </nav>
        <div class="admin-sidebar-foot">
            <a href="<?php echo h(qf_url_page('index.php')); ?>" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> 打开前台
            </a>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-toggle" data-admin-menu aria-label="打开菜单">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <div class="admin-topbar-title"><?php echo h(isset($admin_heading) ? $admin_heading : preg_replace('/\s*-\s*' . preg_quote(SITE_NAME, '/') . '$/', '', $page_title)); ?></div>
            <div class="admin-topbar-user">
                <?php if ($admin_me) { ?>
                    <span><?php echo h(qf_user_display_name($admin_me)); ?></span>
                    <a class="admin-topbar-link" href="<?php echo h(qf_url_page('logout.php')); ?>">退出</a>
                <?php } ?>
            </div>
        </header>
        <main class="admin-content">
