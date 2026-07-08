<?php
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}
$me = current_user();
$unread_notifications = $me ? qf_unread_notifications_count(intval($me['id'])) : 0;
$main_navs = qf_main_navs();
$is_php_theme = true;
$header_forums = array();
$nav_hidden_ids = array_filter(array_map('intval', explode(',', qf_setting('nav_hidden_forums', ''))));
$nav_hidden_sql = !empty($nav_hidden_ids) ? ' WHERE id NOT IN (' . implode(',', $nav_hidden_ids) . ')' : '';
$header_forum_rs = mysqli_query(db(), "SELECT id,name FROM qf_forums{$nav_hidden_sql} ORDER BY display_order ASC, id ASC");
while ($header_forum_rs && ($header_forum = mysqli_fetch_assoc($header_forum_rs))) {
    $header_forums[] = $header_forum;
}
$qf_rss_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-:]/', '', $_SERVER['HTTP_HOST']) : '') . '/feed';
$current_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$page_body_class = 'page-' . preg_replace('/[^a-z0-9_-]+/', '-', strtolower(str_replace('.php', '', $current_script)));
$search_query = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h(qf_browser_title($page_title)); ?></title>
    <base href="<?php echo h(qf_base_href()); ?>">
    <meta name="keywords" content="<?php echo h(qf_site_keywords()); ?>">
    <meta name="description" content="<?php echo h(qf_site_desc()); ?>">
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
    <link rel="stylesheet" href="assets/fonts/fira.css?v=<?php echo filemtime(__DIR__ . '/assets/fonts/fira.css'); ?>">
    <link rel="stylesheet" href="assets/main.css?v=<?php echo filemtime(__DIR__ . '/assets/main.css'); ?>">
    <link rel="alternate" type="application/rss+xml" title="<?php echo h(qf_site_name()); ?> · RSS" href="/feed">
    <style>
        :root {
            --qf-title-font: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', 'PingFang SC', sans-serif;
            --qf-content-font: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', 'PingFang SC', sans-serif;
        }
    </style>
    <script>window.qfCsrfToken = <?php echo json_encode(qf_csrf_token()); ?>; window.qfGeoipUrl = <?php echo json_encode(qf_url_page('api/geoip.php')); ?>;</script>
    <script defer src="assets/lib/preline.min.js"></script>
    <script defer src="assets/lib/alpine.min.js"></script>
</head>
<body class="theme-php <?php echo h($page_body_class); ?>">
<script>
(function () {
    // 深浅色三态：light / dark / system（默认跟随系统）
    try {
        var pref = localStorage.getItem('qfThemeMode') || 'system';
        var dark = pref === 'dark' || (pref === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.body.classList.toggle('theme-php-dark', dark);
    } catch (e) {}
})();
</script>
<?php
$qf_current_forum = null;
if ($current_script === 'forum.php' && function_exists('qf_path_id') && qf_table_has_column('qf_forums', 'banner')) {
    $cf_id = qf_path_id();
    if ($cf_id > 0) {
        $cf_rs = mysqli_query(db(), "SELECT id,name,banner FROM qf_forums WHERE id={$cf_id} LIMIT 1");
        $qf_current_forum = $cf_rs ? mysqli_fetch_assoc($cf_rs) : null;
    }
}
$qf_cur_slug = ($current_script === 'page.php' && isset($_GET['slug'])) ? preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$_GET['slug'])) : '';
$qf_local_banner = qf_header_banner_src($current_script, $qf_cur_slug);
if ($qf_current_forum && !empty($qf_current_forum['banner'])) {
    $qf_page_banner = $qf_current_forum['banner'];
} elseif ($qf_local_banner !== '') {
    $qf_page_banner = $qf_local_banner;
} else {
    $qf_page_banner = qf_setting('default_banner', '');
    if ($qf_page_banner === '') {
        // 未设置则用随机自然图；r 每次请求随机（1-9999），刷新即换图
        $qf_page_banner = 'https://img.et/1920/1080?type=nature&r={r}';
    }
}
if (strpos($qf_page_banner, '{r}') !== false) {
    $qf_page_banner = str_replace('{r}', (string)mt_rand(1, 9999), $qf_page_banner);
}
?>
<div class="qf-page-frame">
<header class="qf-topbar">
    <div class="qf-topbar-inner mx-auto px-3 sm:px-4<?php echo $qf_page_banner !== '' ? ' qf-topbar-hasbg' : ''; ?>">
        <?php if ($qf_page_banner !== '') { ?>
            <img class="qf-topbar-bg" src="<?php echo h($qf_page_banner); ?>" alt="">
            <span class="qf-topbar-scrim"></span>
        <?php } ?>
        <div class="qf-banner relative w-full rounded-t-xl">
            <a class="qf-banner-logo relative z-10 inline-flex items-center" href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="<?php echo h(qf_site_name()); ?>">
                <img class="w-auto" src="assets/logo-white.svg" alt="<?php echo h(qf_site_name()); ?>" draggable="false" oncontextmenu="return false;">
            </a>
            <div class="absolute right-4 top-4 z-10 flex items-center gap-2">
                <?php if (!$me) { ?>
                    <a class="qf-btn qf-btn-ghost<?php echo $current_script === 'login.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('login.php')); ?>">登录</a>
                    <a class="qf-btn qf-btn-solid<?php echo $current_script === 'register.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('register.php')); ?>">注册</a>
                <?php } else { ?>
                    <div class="hs-dropdown relative inline-flex">
                        <button id="qf-user-dd" type="button" class="hs-dropdown-toggle qf-user-trigger" aria-haspopup="menu" aria-expanded="false" aria-label="用户菜单">
                            <img src="<?php echo h(qf_user_avatar($me, 96)); ?>" alt="">
                            <span class="hidden sm:inline"><?php echo h($me['nickname']); ?></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div class="hs-dropdown-menu qf-user-menu hidden" role="menu" aria-labelledby="qf-user-dd">
                            <a href="<?php echo h(qf_url_page('post.php')); ?>"><i class="fa-solid fa-pen-to-square"></i><span>发帖</span></a>
                            <a href="<?php echo h(qf_url_user($me['id'])); ?>"><i class="fa-regular fa-circle-user"></i><span><?php echo h($me['nickname']); ?></span></a>
                            <a href="<?php echo h(qf_url_page('profile.php')); ?>"><i class="fa-solid fa-sliders"></i><span>个人设置</span></a>
                            <a href="<?php echo h(qf_url_page('notifications.php')); ?>"><i class="fa-regular fa-bell"></i><span>消息<?php echo $unread_notifications > 0 ? ' · ' . intval($unread_notifications) : ''; ?></span></a>
                            <?php if (qf_user_signed_today($me['id'])) { ?>
                                <span class="qf-user-static"><i class="fa-solid fa-check"></i><span>已签到</span></span>
                            <?php } else { ?>
                                <form method="post" action="<?php echo h(qf_url_page('signin.php')); ?>"><button type="submit"><i class="fa-solid fa-calendar-check"></i><span>签到</span></button></form>
                            <?php } ?>
                            <?php if (intval($me['is_admin']) === 1) { ?>
                                <a href="<?php echo h(qf_url_page('admin/index.php')); ?>"><i class="fa-solid fa-gauge-high"></i><span>后台</span></a>
                            <?php } ?>
                            <a href="<?php echo h(qf_url_page('logout.php')); ?>"><i class="fa-solid fa-right-from-bracket"></i><span>退出</span></a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <nav class="qf-navbar flex flex-wrap items-center" aria-label="主导航" x-data="{ open: false }">
            <button type="button" class="qf-burger sm:hidden" @click="open = !open" aria-label="展开菜单"><i class="fa-solid fa-bars"></i></button>
            <ul class="qf-menu items-center" :class="open ? 'flex' : 'hidden sm:flex'">
                <li><a class="qf-menu-link<?php echo $current_script === 'index.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('index.php')); ?>"><i class="fa-solid fa-house"></i><span>首页</span></a></li>
                <?php foreach ($header_forums as $forum) { ?>
                    <li><a class="qf-menu-link<?php echo ($qf_current_forum && intval($qf_current_forum['id']) === intval($forum['id'])) ? ' active' : ''; ?>" href="<?php echo h(qf_url_forum($forum['id'])); ?>"><span><?php echo h($forum['name']); ?></span></a></li>
                <?php } ?>
            </ul>
            <div class="qf-navbar-tools">
                <form class="qf-navbar-search" method="get" action="<?php echo h(qf_url_page('search.php')); ?>" role="search" data-navbar-search>
                    <button type="submit" class="qf-navbar-search-btn" aria-label="搜索" title="搜索"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                    <input type="search" name="q" class="qf-navbar-search-input" placeholder="搜索帖子…" autocomplete="off" aria-label="搜索帖子" value="<?php echo h($search_query); ?>">
                </form>
                <button type="button" class="qf-navbar-rss" data-rss-copy data-rss-url="<?php echo h($qf_rss_url); ?>" aria-label="复制 RSS 订阅地址" title="复制 RSS 订阅地址"><i class="fa-solid fa-square-rss" aria-hidden="true"></i></button>
            </div>
        </nav>
    </div>
</header>
<aside class="side-user-menu" aria-label="用户快捷菜单" data-side-user-menu>
    <button class="side-user-trigger" type="button" aria-expanded="false" aria-haspopup="true" data-side-user-toggle>
        <?php if ($me) { ?>
            <img src="<?php echo h(qf_user_avatar($me, 96)); ?>" alt="<?php echo h($me['nickname']); ?>">
        <?php } else { ?>
            <i class="fa-regular fa-circle-user" aria-hidden="true"></i>
        <?php } ?>
    </button>
    <div class="side-user-panel" role="menu" data-side-user-panel>
        <?php if ($me) { ?>
            <div class="side-user-card">
                <img src="<?php echo h(qf_user_avatar($me, 96)); ?>" alt="<?php echo h($me['nickname']); ?>">
                <div>
                    <strong><?php echo h($me['nickname']); ?></strong>
                    <span><?php echo h($me['username']); ?></span>
                </div>
            </div>
            <a href="<?php echo h(qf_url_user($me['id'])); ?>" role="menuitem"><i class="fa-regular fa-circle-user" aria-hidden="true"></i><span>个人主页</span></a>
            <a href="<?php echo h(qf_url_page('profile.php')); ?>" role="menuitem"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>个人设置</span></a>
            <a href="<?php echo h(qf_url_page('notifications.php')); ?>" role="menuitem"><i class="fa-regular fa-bell" aria-hidden="true"></i><span>消息<?php echo $unread_notifications > 0 ? ' · ' . intval($unread_notifications) : ''; ?></span></a>
            <?php if (intval($me['is_admin']) === 1) { ?>
                <a href="<?php echo h(qf_url_page('admin/settings.php')); ?>" role="menuitem"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>站点设置</span></a>
                <a href="<?php echo h(qf_url_page('admin/index.php')); ?>" role="menuitem"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>后台</span></a>
            <?php } ?>
            <a href="<?php echo h(qf_url_page('logout.php')); ?>" role="menuitem"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>退出</span></a>
        <?php } else { ?>
            <a href="<?php echo h(qf_url_page('login.php')); ?>" role="menuitem"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>登录</span></a>
            <a href="<?php echo h(qf_url_page('register.php')); ?>" role="menuitem"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span>注册</span></a>
        <?php } ?>
    </div>
</aside>
<?php if ($unread_notifications > 0 && qf_notification_sound_enabled($me)) { ?>
<script>
if (!sessionStorage.getItem('qfNotifySoundPlayed')) {
    sessionStorage.setItem('qfNotifySoundPlayed', '1');
    try {
        if ('speechSynthesis' in window) {
            var qfMsg = new SpeechSynthesisUtterance('你有新的消息提醒');
            qfMsg.lang = 'zh-CN';
            qfMsg.volume = 0.7;
            window.speechSynthesis.speak(qfMsg);
        }
    } catch (e) {}
}
</script>
<?php } ?>
<?php if (!empty($_SESSION['signin_modal'])) { ?>
    <?php
    $signin_modal_message = $_SESSION['signin_modal'];
    unset($_SESSION['signin_modal']);
    $signin_rank_modal = qf_signin_table_ready() ? mysqli_query(db(), "SELECT u.nickname, u.username, COUNT(s.id) AS total_days FROM qf_users u LEFT JOIN qf_signins s ON s.user_id=u.id GROUP BY u.id HAVING total_days > 0 ORDER BY total_days DESC, u.id ASC LIMIT 5") : false;
    $coin_rank_modal = qf_user_coins_ready() ? mysqli_query(db(), "SELECT nickname, username, coins FROM qf_users WHERE coins>0 ORDER BY coins DESC, id ASC LIMIT 5") : false;
    ?>
    <div class="signin-modal-overlay" id="qf-signin-modal">
        <div class="signin-modal-box">
            <h2>恭喜你签到成功</h2>
            <p class="success"><?php echo h($signin_modal_message); ?></p>
            <div class="signin-rank-mini">
                <div>
                    <h3>签到总天数排名</h3>
                    <ol>
                        <?php $i = 0; while ($signin_rank_modal && $row = mysqli_fetch_assoc($signin_rank_modal)) { $i++; ?>
                            <li><?php echo h($row['nickname'] !== '' ? $row['nickname'] : $row['username']); ?> · <?php echo intval($row['total_days']); ?>天</li>
                        <?php } ?>
                        <?php if ($i === 0) { ?><li>暂无记录</li><?php } ?>
                    </ol>
                </div>
                <div>
                    <h3>金币排行榜</h3>
                    <ol>
                        <?php $i = 0; while ($coin_rank_modal && $row = mysqli_fetch_assoc($coin_rank_modal)) { $i++; ?>
                            <li><?php echo h($row['nickname'] !== '' ? $row['nickname'] : $row['username']); ?> · <?php echo intval($row['coins']); ?>金币</li>
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
