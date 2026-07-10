</main>
</div><!-- /pd-page-frame -->
<?php
$footer_friend_links = array();
if (pd_friend_links_enabled()) {
    $footer_friend_links = pd_friend_links();
}
$footer_pages = array(
    array('title' => '关于', 'url' => pd_url_page('about.php')),
    array('title' => '帮助', 'url' => pd_url_page('page.php', array('slug' => 'help'))),
    array('title' => '规则', 'url' => pd_url_page('page.php', array('slug' => 'rules'))),
    array('title' => '隐私政策', 'url' => pd_url_page('page.php', array('slug' => 'privacy'))),
);
foreach (pd_footer_nav_forums() as $footer_forum) {
    $footer_pages[] = array('title' => $footer_forum['name'], 'url' => pd_url_forum(intval($footer_forum['id'])));
}
$footer_social_links = array(
    array('title' => 'GitHub', 'url' => 'https://github.com/gentpan/php.do', 'icon' => 'fa-brands fa-github'),
    array('title' => 'X', 'url' => 'https://x.com/phpdo', 'icon' => 'fa-brands fa-x-twitter'),
);
$footer_user = current_user();
if (!isset($current_script)) {
    $current_script = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
}
$footer_unread_pm = $footer_user ? pd_pm_unread_count(intval($footer_user['id'])) : 0;
$footer_unread_notifications = $footer_user ? pd_unread_notifications_count(intval($footer_user['id'])) : 0;
$footer_signed_today = $footer_user ? pd_user_signed_today(intval($footer_user['id'])) : false;
$footer_is_admin = $footer_user && intval($footer_user['is_admin']) === 1;
$footer_login_url = pd_url_page('login.php');
$footer_home_url = pd_url_page('index.php');
$footer_user_url = $footer_user ? pd_url_user(intval($footer_user['id'])) : $footer_login_url;
$footer_messages_url = $footer_user ? pd_url_messages() : $footer_login_url;
$footer_notifications_url = $footer_user ? pd_url_page('notifications.php') : $footer_login_url;
$footer_profile_url = $footer_user ? pd_url_page('profile.php') : $footer_login_url;
$footer_post_url = $footer_user ? pd_url_page('post.php') : $footer_login_url;
$footer_signin_url = $footer_user ? pd_url_page('signin.php') : $footer_login_url;
$footer_logout_url = $footer_user ? pd_url_page('logout.php') : $footer_login_url;
$footer_admin_url = '/admin';
$footer_user_page_id = ($current_script === 'user.php') ? (function_exists('pd_path_id') ? pd_path_id() : intval(isset($_GET['id']) ? $_GET['id'] : 0)) : 0;
$footer_rail_home_active = ($current_script === 'index.php');
$footer_rail_user_active = ($footer_user && $current_script === 'user.php' && $footer_user_page_id === intval($footer_user['id']));
$footer_rail_messages_active = ($current_script === 'messages.php');
$footer_rail_notifications_active = ($current_script === 'notifications.php');
$footer_rail_profile_active = ($current_script === 'profile.php');
$footer_icp = trim(pd_setting('icp_code', ''));
$online = pd_online_counts();
?>
<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-row site-footer-row1">
            <a class="site-footer-logo" href="<?php echo h(pd_url_page('index.php')); ?>" aria-label="<?php echo h(pd_site_name()); ?>">
                <img src="assets/logo-white.svg" alt="<?php echo h(pd_site_name()); ?>" draggable="false" oncontextmenu="return false;">
            </a>
            <div class="site-footer-stats">
                <span class="pd-stat-chip pd-stat-chip--sql" title="数据库查询次数">
                    <span class="pd-stat-chip__ico" aria-hidden="true"><i class="fa-solid fa-database"></i></span>
                    <span class="pd-stat-chip__body">
                        <span class="pd-stat-chip__k">SQL</span>
                        <span class="pd-stat-chip__v"><?php echo intval(pd_perf_sql_count()); ?></span>
                    </span>
                </span>
                <span class="pd-stat-chip pd-stat-chip--time" title="页面生成耗时">
                    <span class="pd-stat-chip__ico" aria-hidden="true"><i class="fa-solid fa-stopwatch"></i></span>
                    <span class="pd-stat-chip__body">
                        <span class="pd-stat-chip__k">耗时</span>
                        <span class="pd-stat-chip__v"><?php echo number_format(pd_perf_seconds(), 3); ?>s</span>
                    </span>
                </span>
                <span class="pd-stat-chip pd-stat-chip--online" title="当前在线人数">
                    <span class="pd-stat-chip__ico" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
                    <span class="pd-stat-chip__body">
                        <span class="pd-stat-chip__k">在线</span>
                        <span class="pd-stat-chip__v"><?php echo intval($online['total']); ?></span>
                    </span>
                </span>
            </div>
            <div class="site-footer-social">
                <?php foreach ($footer_social_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener" title="<?php echo h($link['title']); ?>" aria-label="<?php echo h($link['title']); ?>">
                        <i class="<?php echo h($link['icon']); ?>" aria-hidden="true"></i>
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="site-footer-row site-footer-row2">
            <span class="site-footer-copy">&copy; <?php echo date('Y'); ?> <?php echo h(pd_site_name()); ?>. All rights reserved.<?php if ($footer_icp !== '') { ?> · <span class="site-footer-icp"><?php echo nl2br(h($footer_icp)); ?></span><?php } ?></span>
            <nav class="site-footer-links" aria-label="站点链接">
                <?php foreach ($footer_pages as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>"><?php echo h($link['title']); ?></a>
                <?php } ?>
            </nav>
        </div>
        <?php if (!empty($footer_friend_links)) { ?>
        <nav class="site-footer-row site-footer-friends" aria-label="友情链接">
            <span class="site-footer-friends-label">友情链接</span>
            <?php foreach ($footer_friend_links as $link) { ?>
                <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
            <?php } ?>
        </nav>
        <?php } ?>
    </div>
</footer>
<div class="pd-search-window" id="pd-search-modal" data-search-close>
    <div class="pd-search-window-box">
        <form method="get" action="<?php echo h(pd_url_page('search.php')); ?>" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input name="q" placeholder="搜索帖子、版块、分类…" autocomplete="off" aria-label="搜索">
            <kbd>Esc</kbd>
        </form>
        <div class="pd-search-window-hint">回车搜索 · <kbd>Esc</kbd> 关闭 · <kbd>⌘/Ctrl</kbd>&nbsp;<kbd>K</kbd> 或 <kbd>/</kbd> 打开</div>
    </div>
</div>
<nav class="cir-rail pd-right-toolbar" aria-label="页面工具栏">
    <button type="button" class="cir-rail__b" data-scroll-top aria-label="返回顶部" data-tooltip="返回顶部">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>
    <span class="cir-rail__sep cir-rail__sep--scroll" aria-hidden="true"></span>
    <a href="<?php echo h($footer_home_url); ?>" class="cir-rail__b cir-rail__b--brand<?php echo $footer_rail_home_active ? ' cir-rail__b--active' : ''; ?>" aria-label="首页" data-tooltip="首页"<?php echo $footer_rail_home_active ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 3 3 10v10a1 1 0 0 0 1 1h5v-6h6v6h5a1 1 0 0 0 1-1V10z"></path>
        </svg>
    </a>
    <a href="<?php echo h($footer_user_url); ?>" class="cir-rail__b<?php echo $footer_rail_user_active ? ' cir-rail__b--active' : ''; ?>" aria-label="个人中心" data-tooltip="个人中心"<?php echo $footer_rail_user_active ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>
    <a href="<?php echo h($footer_messages_url); ?>" class="cir-rail__b<?php echo $footer_rail_messages_active ? ' cir-rail__b--active' : ''; ?>" aria-label="私信" data-tooltip="私信"<?php echo $footer_rail_messages_active ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <?php if ($footer_unread_pm > 0) { ?>
            <span class="cir-rail__badge"><?php echo $footer_unread_pm > 99 ? '99+' : intval($footer_unread_pm); ?></span>
        <?php } ?>
    </a>
    <a href="<?php echo h($footer_notifications_url); ?>" class="cir-rail__b<?php echo $footer_rail_notifications_active ? ' cir-rail__b--active' : ''; ?>" aria-label="系统消息" data-tooltip="系统消息"<?php echo $footer_rail_notifications_active ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <?php if ($footer_unread_notifications > 0) { ?>
            <span class="cir-rail__badge"><?php echo $footer_unread_notifications > 99 ? '99+' : intval($footer_unread_notifications); ?></span>
        <?php } ?>
    </a>
    <a href="<?php echo h($footer_post_url); ?>" class="cir-rail__b" aria-label="发帖" data-tooltip="发帖">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
        </svg>
    </a>
    <span class="cir-rail__sep" aria-hidden="true"></span>
    <a href="<?php echo h($footer_profile_url); ?>" class="cir-rail__b<?php echo $footer_rail_profile_active ? ' cir-rail__b--active' : ''; ?>" aria-label="设置" data-tooltip="设置"<?php echo $footer_rail_profile_active ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
    </a>
    <?php if ($footer_user) { ?>
        <?php if ($footer_signed_today) { ?>
            <span class="cir-rail__b cir-rail__b--done" aria-label="今日已签到" data-tooltip="今日已签到">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <path d="m9 16 2 2 4-4"></path>
                </svg>
            </span>
        <?php } else { ?>
            <form class="cir-rail__form" method="post" action="<?php echo h($footer_signin_url); ?>">
                <button type="submit" class="cir-rail__b" aria-label="签到" data-tooltip="签到">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <path d="m9 16 2 2 4-4"></path>
                    </svg>
                </button>
            </form>
        <?php } ?>
        <?php if ($footer_is_admin) { ?>
            <a href="<?php echo h($footer_admin_url); ?>" class="cir-rail__b" aria-label="后台" data-tooltip="后台">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20V10"></path>
                    <path d="M18 20V4"></path>
                    <path d="M6 20v-4"></path>
                </svg>
            </a>
        <?php } ?>
    <?php } ?>
    <button type="button" class="cir-rail__b" data-theme-toggle data-theme-mode="system" aria-label="主题：跟随系统" data-tooltip="跟随系统">
        <svg data-theme-icon="light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 2v2"></path>
            <path d="M12 20v2"></path>
            <path d="m4.93 4.93 1.41 1.41"></path>
            <path d="m17.66 17.66 1.41 1.41"></path>
            <path d="M2 12h2"></path>
            <path d="M20 12h2"></path>
            <path d="m4.93 19.07 1.41-1.41"></path>
            <path d="m17.66 6.34 1.41-1.41"></path>
        </svg>
        <svg data-theme-icon="dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
        <svg data-theme-icon="system" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
        </svg>
    </button>
    <?php if ($footer_user) { ?>
        <span class="cir-rail__sep" aria-hidden="true"></span>
        <form class="cir-rail__form" method="post" action="<?php echo h($footer_logout_url); ?>">
        <?php echo pd_csrf_field(); ?>
        <button type="submit" class="cir-rail__b cir-rail__b--logout" aria-label="退出" data-tooltip="退出">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </button>
        </form>
    <?php } ?>
</nav>
<script src="assets/lib/litezoom.min.js"></script>
<script src="<?php echo h(pd_asset_js('main', 'assets/')); ?>"></script>
<script>
(function () {
    // 搜索模态窗：⌘/Ctrl+K 或 / 打开，Esc / 点击遮罩关闭
    (function () {
        var modal = document.getElementById('pd-search-modal');
        if (!modal) return;
        var input = modal.querySelector('input[name="q"]');
        function openWin() {
            modal.classList.add('is-open');
            if (input) window.setTimeout(function () { input.focus(); input.select(); }, 30);
        }
        function closeWin() { modal.classList.remove('is-open'); }
        modal.addEventListener('click', function (e) { if (e.target === modal) closeWin(); });
        document.addEventListener('keydown', function (e) {
            var t = e.target || {};
            var typing = /^(input|textarea|select)$/i.test(t.tagName || '') || t.isContentEditable;
            if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); openWin(); return; }
            if (e.key === '/' && !typing && !modal.classList.contains('is-open')) { e.preventDefault(); openWin(); return; }
            if (e.key === 'Escape' && modal.classList.contains('is-open')) { closeWin(); }
        });
    })();

    // 深浅色三态切换：跟随系统 → 浅色 → 深色 → 跟随系统
    (function () {
        var order = ['system', 'light', 'dark'];
        var labels = { system: '跟随系统', light: '浅色', dark: '深色' };
        function pref() { try { return localStorage.getItem('pdThemeMode') || 'system'; } catch (e) { return 'system'; } }
        function systemDark() { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
        function apply(p) { document.body.classList.toggle('theme-pd-dark', p === 'dark' || (p === 'system' && systemDark())); }
        function refresh(p) {
            var btns = document.querySelectorAll('[data-theme-toggle]');
            for (var i = 0; i < btns.length; i++) {
                var btn = btns[i];
                btn.setAttribute('data-theme-mode', p);
                var label = '主题：' + labels[p] + '（点击切换）';
                btn.setAttribute('title', label);
                btn.setAttribute('aria-label', label);
                btn.setAttribute('data-tooltip', labels[p]);
                var ic = btn.querySelector('i');
                if (ic) {
                    var icons = { system: 'fa-circle-half-stroke', light: 'fa-sun', dark: 'fa-moon' };
                    ic.className = 'fa-solid ' + icons[p];
                }
            }
        }
        var cur = pref();
        apply(cur); refresh(cur);
        if (window.matchMedia) {
            try {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                    if (pref() === 'system') apply('system');
                });
            } catch (e) {}
        }
        var btns = document.querySelectorAll('[data-theme-toggle]');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function () {
                var next = order[(order.indexOf(pref()) + 1) % order.length];
                try { localStorage.setItem('pdThemeMode', next); } catch (e) {}
                apply(next); refresh(next);
            });
        }
    })();

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) return;
        if (a.getAttribute('target') === '_blank' || a.hasAttribute('download')) return;
        if (a.hasAttribute('data-no-global-loading')) return;
        var href = a.getAttribute('href') || '';
        if (href === '' || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
        try { if (a.origin && a.origin !== location.origin) return; } catch (err) { return; }
        // 延后一拍：若点击被页面内的 JS（AJAX 筛选/翻页等）接管并取消了跳转，
        // e.defaultPrevented 会变为 true，这时不显示加载层，避免模糊层卡死。
        window.setTimeout(function () {
            if (e.defaultPrevented) return;
            if (typeof window.pdSetLoading !== 'function') return;
            window.pdSetLoading(true, 'page');
            // 兜底：万一跳转最终没有发生，10 秒后自动清除加载层。
            window.setTimeout(function () {
                if (typeof window.pdSetLoading === 'function') window.pdSetLoading(false, 'page');
            }, 10000);
        }, 0);
    });
})();
</script>
<script defer src="https://tongji.giantaccel.com/script.js" data-website-id="ae0c1e6e-e652-480f-a501-5ec214700ddd"></script>
<script defer src="https://tongji.giantaccel.com/recorder.js" data-website-id="ae0c1e6e-e652-480f-a501-5ec214700ddd"></script>
<?php echo pd_setting('stats_code', ''); ?>
</body>
</html>
