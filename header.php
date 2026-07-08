<?php
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}
$me = current_user();
pd_ensure_timezone_schema();
pd_migrate_schema_prefix_from_qf();
pd_migrate_forum_nav_plan_a();
$unread_notifications = $me ? pd_unread_notifications_count(intval($me['id'])) : 0;
pd_online_touch();
$main_navs = pd_main_navs();
$is_pd_theme = true;
$header_forums = pd_header_nav_forums();
$pd_rss_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-:]/', '', $_SERVER['HTTP_HOST']) : '') . '/feed';
$current_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$page_body_class = 'page-' . preg_replace('/[^a-z0-9_-]+/', '-', strtolower(str_replace('.php', '', $current_script)));
$search_query = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h(pd_browser_title($page_title)); ?></title>
    <base href="<?php echo h(pd_base_href()); ?>">
    <meta name="keywords" content="<?php echo h(pd_site_keywords()); ?>">
    <meta name="description" content="<?php echo h(pd_site_desc()); ?>">
    <link rel="icon" href="assets/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="manifest" href="assets/site.webmanifest">
    <meta name="theme-color" content="#262626">
    <meta name="msapplication-TileColor" content="#505b93">
    <meta name="msapplication-TileImage" content="assets/mstile-150x150.png">
    <link rel="stylesheet" href="https://static.bluecdn.com/libs/fontawesome/7.3.0/css/all.min.css">
    <link rel="stylesheet" href="https://flagcdn.io/css/flag-icons.min.css">
    <link rel="stylesheet" href="assets/fonts/fira.css?v=<?php echo filemtime(__DIR__ . '/assets/fonts/fira.css'); ?>">
    <link rel="stylesheet" href="assets/main.css?v=<?php echo filemtime(__DIR__ . '/assets/main.css'); ?>">
    <?php if (!empty($pd_lite_layout) || $current_script === 'search.php') { ?>
    <link rel="stylesheet" href="assets/standalone.css?v=<?php echo filemtime(__DIR__ . '/assets/standalone.css'); ?>">
    <?php } ?>
    <link rel="alternate" type="application/rss+xml" title="<?php echo h(pd_site_name()); ?> · RSS" href="/feed">
    <style>
        :root {
            --pd-title-font: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', 'PingFang SC', sans-serif;
            --pd-content-font: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', 'PingFang SC', sans-serif;
        }
    </style>
    <script>window.pdCsrfToken = <?php echo json_encode(pd_csrf_token()); ?>; window.pdGeoipUrl = <?php echo json_encode(pd_url_page('api/geoip.php')); ?>; window.pdUserTimezone = <?php echo json_encode(pd_user_timezone($me)); ?>; window.pdCurrentUserId = <?php echo json_encode($me ? intval($me['id']) : 0); ?>;</script>
    <script defer src="assets/lib/preline.min.js"></script>
    <script defer src="assets/lib/alpine.min.js"></script>
</head>
<body class="theme-pd <?php echo h($page_body_class); ?><?php echo !empty($pd_lite_layout) ? ' pd-standalone' : ''; ?>">
<script>
(function () {
    // 深浅色三态：light / dark / system（默认跟随系统）
    try {
        var pref = localStorage.getItem('pdThemeMode') || 'system';
        var dark = pref === 'dark' || (pref === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.body.classList.toggle('theme-pd-dark', dark);
    } catch (e) {}

    // 首页彩色条：仅初次进入 / 强制刷新，尽早显示（main.js 稍后接管进度）
    try {
        if (!document.body.classList.contains('page-index')) return;
        var navType = 'navigate';
        try {
            var entries = performance.getEntriesByType && performance.getEntriesByType('navigation');
            if (entries && entries.length && entries[0].type) navType = entries[0].type;
            else if (performance.navigation) navType = performance.navigation.type;
        } catch (e1) {}
        if (navType === 2 || navType === 'back_forward') return;
        var show = (navType === 1 || navType === 'reload');
        if (!show) {
            if (document.referrer) {
                var ref = new URL(document.referrer);
                if (ref.origin === location.origin) return;
            }
            show = true;
        }
        if (!show) return;
        document.body.classList.add('pd-is-bar-loading');
        if (!document.querySelector('.pd-topload')) {
            var bar = document.createElement('div');
            bar.className = 'pd-topload';
            bar.setAttribute('aria-hidden', 'true');
            bar.innerHTML = '<div class="progress-container"><div class="progress-bar" style="width:1%"></div><div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div><div class="progress-text">1%</div></div>';
            document.body.appendChild(bar);
        }
        window.__pdHomeBarEarly = true;
    } catch (e2) {}
})();
</script>
<?php
$pd_current_forum = null;
if ($current_script === 'forum.php' && function_exists('pd_path_id') && pd_table_has_column('pd_forums', 'banner')) {
    $cf_id = pd_path_id();
    if ($cf_id > 0) {
        $cf_rs = mysqli_query(db(), "SELECT id,name,banner FROM pd_forums WHERE id={$cf_id} LIMIT 1");
        $pd_current_forum = $cf_rs ? mysqli_fetch_assoc($cf_rs) : null;
    }
}
$pd_cur_slug = ($current_script === 'page.php' && isset($_GET['slug'])) ? preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$_GET['slug'])) : '';
$pd_local_banner = pd_header_banner_src($current_script, $pd_cur_slug);
if ($pd_current_forum && !empty($pd_current_forum['banner'])) {
    $pd_page_banner = $pd_current_forum['banner'];
} elseif ($pd_local_banner !== '') {
    $pd_page_banner = $pd_local_banner;
} else {
    $pd_page_banner = pd_setting('default_banner', '');
    if ($pd_page_banner === '') {
        // 未设置则用随机自然图；r 每次请求随机（1-9999），刷新即换图
        $pd_page_banner = 'https://img.et/1920/1080?type=nature&r={r}';
    }
}
if (strpos($pd_page_banner, '{r}') !== false) {
    $pd_page_banner = str_replace('{r}', (string)mt_rand(1, 9999), $pd_page_banner);
}
?>
<div class="pd-page-frame">
<header class="pd-topbar">
    <div class="pd-topbar-inner mx-auto px-3 sm:px-4<?php echo $pd_page_banner !== '' ? ' pd-topbar-hasbg' : ''; ?>">
        <?php if ($pd_page_banner !== '') { ?>
            <img class="pd-topbar-bg" src="<?php echo h($pd_page_banner); ?>" alt="">
            <span class="pd-topbar-scrim"></span>
        <?php } ?>
        <div class="pd-banner relative w-full rounded-t-xl">
            <a class="pd-banner-logo relative z-10 inline-flex items-center" href="<?php echo h(pd_url_page('index.php')); ?>" aria-label="<?php echo h(pd_site_name()); ?>">
                <img class="w-auto" src="assets/logo-white.svg" alt="<?php echo h(pd_site_name()); ?>" draggable="false" oncontextmenu="return false;">
            </a>
            <?php if (empty($pd_lite_layout)) { ?>
            <div class="absolute right-4 top-4 z-10 flex items-center gap-2">
                <?php if (!$me) { ?>
                    <a class="pd-btn pd-btn-ghost<?php echo $current_script === 'login.php' ? ' active' : ''; ?>" href="<?php echo h(pd_url_page('login.php')); ?>">登录</a>
                    <a class="pd-btn pd-btn-solid<?php echo $current_script === 'register.php' ? ' active' : ''; ?>" href="<?php echo h(pd_url_page('register.php')); ?>">注册</a>
                <?php } else { ?>
                    <a class="pd-user-avatar-link" href="<?php echo h(pd_url_user(intval($me['id']))); ?>" aria-label="<?php echo h($me['nickname']); ?>">
                        <img src="<?php echo h(pd_user_avatar($me, 96)); ?>" alt="" width="36" height="36" loading="lazy">
                    </a>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
        <?php if (empty($pd_lite_layout)) { ?>
        <nav class="pd-navbar flex flex-wrap items-center" aria-label="主导航" x-data="{ open: false }">
            <button type="button" class="pd-burger sm:hidden" @click="open = !open" aria-label="展开菜单"><i class="fa-solid fa-bars"></i></button>
            <ul class="pd-menu items-center" :class="open ? 'flex' : 'hidden sm:flex'">
                <li><a class="pd-menu-link<?php echo $current_script === 'index.php' ? ' active' : ''; ?>" href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house"></i><span>首页</span></a></li>
                <?php foreach ($header_forums as $forum) { ?>
                    <li><a class="pd-menu-link<?php echo ($pd_current_forum && intval($pd_current_forum['id']) === intval($forum['id'])) ? ' active' : ''; ?>" href="<?php echo h(pd_url_forum($forum['id'])); ?>"><i class="<?php echo h(pd_forum_icon($forum)); ?>" aria-hidden="true"></i><span><?php echo h($forum['name']); ?></span></a></li>
                <?php } ?>
            </ul>
            <div class="pd-navbar-tools">
                <form class="pd-navbar-search" method="get" action="<?php echo h(pd_url_page('search.php')); ?>" role="search" data-navbar-search>
                    <button type="submit" class="pd-navbar-search-btn" aria-label="搜索" title="搜索"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                    <input type="search" name="q" class="pd-navbar-search-input" placeholder="搜索帖子…" autocomplete="off" aria-label="搜索帖子" value="<?php echo h($search_query); ?>">
                </form>
                <button type="button" class="pd-navbar-rss" data-rss-copy data-rss-url="<?php echo h($pd_rss_url); ?>" aria-label="复制 RSS 订阅地址" title="复制 RSS 订阅地址"><i class="fa-solid fa-square-rss" aria-hidden="true"></i></button>
            </div>
        </nav>
        <?php } ?>
    </div>
</header>
<?php if ($unread_notifications > 0 && pd_notification_sound_enabled($me)) { ?>
<script>
if (!sessionStorage.getItem('pdNotifySoundPlayed')) {
    sessionStorage.setItem('pdNotifySoundPlayed', '1');
    try {
        if ('speechSynthesis' in window) {
            var pdMsg = new SpeechSynthesisUtterance('你有新的消息提醒');
            pdMsg.lang = 'zh-CN';
            pdMsg.volume = 0.7;
            window.speechSynthesis.speak(pdMsg);
        }
    } catch (e) {}
}
</script>
<?php } ?>
<?php if (!empty($_SESSION['signin_modal'])) { ?>
    <?php
    $signin_modal_message = $_SESSION['signin_modal'];
    unset($_SESSION['signin_modal']);
    $signin_rank_modal = pd_signin_table_ready() ? mysqli_query(db(), "SELECT u.nickname, u.username, COUNT(s.id) AS total_days FROM pd_users u LEFT JOIN pd_signins s ON s.user_id=u.id GROUP BY u.id HAVING total_days > 0 ORDER BY total_days DESC, u.id ASC LIMIT 5") : false;
    $coin_rank_modal = pd_user_coins_ready() ? mysqli_query(db(), "SELECT nickname, username, coins FROM pd_users WHERE coins>0 ORDER BY coins DESC, id ASC LIMIT 5") : false;
    ?>
    <div class="signin-modal-overlay" id="pd-signin-modal">
        <div class="signin-modal-box">
            <h2>恭喜你签到成功</h2>
            <p class="success"><?php echo h($signin_modal_message); ?></p>
            <div class="signin-rank-mini">
                <div>
                    <h3>签到总天数排名</h3>
                    <ol>
                        <?php $i = 0; while ($signin_rank_modal && $row = mysqli_fetch_assoc($signin_rank_modal)) { $i++; ?>
                            <li><?php echo h(pd_user_display_name($row)); ?> · <?php echo intval($row['total_days']); ?>天</li>
                        <?php } ?>
                        <?php if ($i === 0) { ?><li>暂无记录</li><?php } ?>
                    </ol>
                </div>
                <div>
                    <h3>金币排行榜</h3>
                    <ol>
                        <?php $i = 0; while ($coin_rank_modal && $row = mysqli_fetch_assoc($coin_rank_modal)) { $i++; ?>
                            <li><?php echo h(pd_user_display_name($row)); ?> · <?php echo intval($row['coins']); ?>金币</li>
                        <?php } ?>
                        <?php if ($i === 0) { ?><li>暂无记录</li><?php } ?>
                    </ol>
                </div>
            </div>
            <button class="btn" type="button" data-signin-close>确认</button>
        </div>
    </div>
<?php } ?>
<main class="wrap">
