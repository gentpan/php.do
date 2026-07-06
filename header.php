<?php
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}
$me = current_user();
$unread_notifications = $me ? qf_unread_notifications_count(intval($me['id'])) : 0;
$main_navs = qf_main_navs();
$active_theme = qf_theme();
$is_php_theme = in_array($active_theme, array('php', 'php-dark'), true);
$header_forums = array();
if ($is_php_theme) {
    $header_forum_rs = mysqli_query(db(), "SELECT id,name FROM qf_forums ORDER BY display_order ASC, id ASC");
    while ($header_forum_rs && ($header_forum = mysqli_fetch_assoc($header_forum_rs))) {
        $header_forums[] = $header_forum;
    }
}
$current_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$current_path = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$use_system_page_font = $current_script === 'profile.php' || preg_match('#/admin/settings\.php$#', $current_path);
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
    <meta name="theme-color" content="<?php echo $active_theme === 'php-dark' ? '#101217' : '#5d29f0'; ?>">
    <meta name="msapplication-TileColor" content="#5d29f0">
    <meta name="msapplication-TileImage" content="assets/mstile-150x150.png">
    <link rel="stylesheet" href="https://static.bluecdn.com/libs/fontawesome/7.3.0/css/all.min.css">
    <?php if ($is_php_theme) { ?>
        <link rel="stylesheet" href="https://www.php.net/cached.php?t=1781787603&amp;f=/fonts/Fira/fira.css" media="print" onload="this.onload=null;this.media='all'">
    <?php } ?>
    <?php if (!$use_system_page_font) { foreach (qf_selected_font_urls() as $font_url) { ?>
        <link rel="stylesheet" href="<?php echo h($font_url); ?>">
    <?php } } ?>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo filemtime(__DIR__ . '/assets/style.css'); ?>">
    <style>
        :root {
            --qf-title-font: <?php echo qf_font_family('title_font'); ?>;
            --qf-content-font: <?php echo qf_font_family('content_font'); ?>;
        }
    </style>
    <script>window.qfCsrfToken = <?php echo json_encode(qf_csrf_token()); ?>;</script>
</head>
<body class="<?php echo $active_theme === 'php-dark' ? 'theme-php theme-php-dark' : 'theme-' . h($active_theme); ?> <?php echo h($page_body_class); ?><?php echo $use_system_page_font ? ' use-system-page-font' : ''; ?>">
<?php if ($is_php_theme) { ?>
<script>
(function () {
    try {
        var pref = localStorage.getItem('qfThemeMode');
        if (pref === 'dark') document.body.classList.add('theme-php-dark');
        else if (pref === 'light') document.body.classList.remove('theme-php-dark');
    } catch (e) {}
})();
</script>
<?php } ?>
<nav class="site-nav-bar" aria-label="主导航">
    <div class="nav-pill" data-nav-shell>
        <div class="nav-row">
            <div class="nav-identity-orb">
                <a class="nav-avatar<?php echo $current_script === 'index.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="<?php echo h(qf_site_name()); ?>">
                    <img class="php-wordmark" src="assets/logo.svg" alt="" aria-hidden="true">
                    <span class="nav-avatar-home" aria-hidden="true"><i class="fa-solid fa-house"></i></span>
                </a>
            </div>
            <div class="nav-actions">
                <form class="nav-search-form" method="get" action="<?php echo h(qf_url_page('search.php')); ?>" role="search">
                    <input name="q" value="<?php echo h($current_script === 'search.php' ? $search_query : ''); ?>" placeholder="Search" autocomplete="search">
                    <button type="submit" aria-label="搜索"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </form>
                <button type="button" class="nav-theme-toggle" data-theme-toggle aria-label="切换深色/浅色">
                    <i class="fa-solid fa-sun nav-theme-sun" aria-hidden="true"></i>
                    <i class="fa-solid fa-moon nav-theme-moon" aria-hidden="true"></i>
                </button>
                <?php if (!$me) { ?>
                    <a class="nav-cta nav-login-link<?php echo $current_script === 'login.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('login.php')); ?>"><span>登录</span></a>
                    <a class="nav-more-toggle nav-register-link<?php echo $current_script === 'register.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('register.php')); ?>"><span>注册</span></a>
                <?php } else { ?>
                    <a class="nav-cta<?php echo $current_script === 'post.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('post.php')); ?>">
                        <i class="fa-solid fa-pen-to-square nav-activity-bars" aria-hidden="true"></i>
                        <i class="fa-solid fa-plus nav-cta-mobile-icon" aria-hidden="true"></i>
                        <span>发帖</span>
                    </a>
                    <button type="button" class="nav-more-toggle" data-nav-more aria-label="更多" aria-expanded="false" aria-haspopup="true">
                        <i class="fa-solid fa-bars nav-more-bars" aria-hidden="true"></i>
                        <i class="fa-solid fa-circle-xmark nav-more-close" aria-hidden="true"></i>
                        <span>更多</span>
                    </button>
                <?php } ?>
                <div class="nav-more-menu" data-nav-more-menu role="menu" aria-label="更多入口">
                    <?php if ($me) { ?>
                        <a class="nav-more-item" href="<?php echo h(qf_url_user($me['id'])); ?>" role="menuitem">
                            <i class="fa-regular fa-circle-user" aria-hidden="true"></i>
                            <span><?php echo h($me['nickname']); ?></span>
                        </a>
                        <a class="nav-more-item" href="<?php echo h(qf_url_page('profile.php')); ?>" role="menuitem">
                            <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                            <span>个人设置</span>
                        </a>
                        <a class="nav-more-item" href="<?php echo h(qf_url_page('notifications.php')); ?>" role="menuitem">
                            <i class="fa-regular fa-bell" aria-hidden="true"></i>
                            <span>消息<?php echo $unread_notifications > 0 ? ' · ' . intval($unread_notifications) : ''; ?></span>
                        </a>
                        <?php if (qf_user_signed_today($me['id'])) { ?>
                            <span class="nav-more-item is-static" role="menuitem"><i class="fa-solid fa-check" aria-hidden="true"></i><span>已签到</span></span>
                        <?php } else { ?>
                            <form class="nav-more-form signin-form" method="post" action="<?php echo h(qf_url_page('signin.php')); ?>" role="none">
                                <button class="nav-more-item" type="submit" role="menuitem"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i><span>签到</span></button>
                            </form>
                        <?php } ?>
                        <?php if (intval($me['is_admin']) === 1) { ?>
                            <a class="nav-more-item" href="<?php echo h(qf_url_page('admin/index.php')); ?>" role="menuitem"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>后台</span></a>
                        <?php } ?>
                        <a class="nav-more-item" href="<?php echo h(qf_url_page('logout.php')); ?>" role="menuitem"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>退出</span></a>
                    <?php } else { ?>
                        <a class="nav-more-item" href="<?php echo h(qf_url_page('login.php')); ?>" role="menuitem"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>登录</span></a>
                        <a class="nav-more-item" href="<?php echo h(qf_url_page('register.php')); ?>" role="menuitem"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span>注册</span></a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</nav>
<?php if (!empty($main_navs)) { ?>
<nav class="qf-category-nav" aria-label="分类导航">
    <div class="qf-category-nav-inner">
        <a class="qf-category-link<?php echo $current_script === 'index.php' ? ' active' : ''; ?>" href="<?php echo h(qf_url_page('index.php')); ?>">
            <i class="qf-cat-icon fa-solid fa-house" aria-hidden="true"></i>
            <span>首页</span>
        </a>
        <?php foreach ($main_navs as $nav_item) { ?>
            <a class="qf-category-link" href="<?php echo h(qf_url_nav($nav_item['url'])); ?>"<?php echo qf_nav_target($nav_item['url']); ?>>
                <?php echo qf_nav_icon_html($nav_item); ?>
                <span><?php echo h($nav_item['title']); ?></span>
            </a>
        <?php } ?>
    </div>
</nav>
<?php } ?>
<?php if ($is_php_theme && $current_script !== 'index.php' && !empty($header_forums)) { ?>
<nav class="phpdo-inner-category-bar" aria-label="论坛分类">
    <div>
        <?php foreach ($header_forums as $header_forum) { ?>
            <a href="<?php echo h(qf_url_forum($header_forum['id'])); ?>"><?php echo h($header_forum['name']); ?></a>
        <?php } ?>
    </div>
</nav>
<?php } ?>
<aside class="side-user-menu" aria-label="用户快捷菜单" data-side-user-menu>
    <button class="side-user-trigger" type="button" aria-expanded="false" aria-haspopup="true" data-side-user-toggle>
        <?php if ($me) { ?>
            <img src="<?php echo h($me['avatar'] !== '' ? $me['avatar'] : 'assets/avatar-default.svg'); ?>" alt="<?php echo h($me['nickname']); ?>">
        <?php } else { ?>
            <i class="fa-regular fa-circle-user" aria-hidden="true"></i>
        <?php } ?>
    </button>
    <div class="side-user-panel" role="menu" data-side-user-panel>
        <?php if ($me) { ?>
            <div class="side-user-card">
                <img src="<?php echo h($me['avatar'] !== '' ? $me['avatar'] : 'assets/avatar-default.svg'); ?>" alt="<?php echo h($me['nickname']); ?>">
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
