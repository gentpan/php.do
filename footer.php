</main>
</div><!-- /qf-page-frame -->
<?php
$footer_friend_links = array();
if (qf_friend_links_enabled()) {
    $footer_friend_links = qf_friend_links();
}
$footer_pages = array(
    array('title' => '首页', 'url' => qf_url_page('index.php')),
    array('title' => '搜索', 'url' => qf_url_page('search.php')),
    array('title' => '关于', 'url' => qf_url_page('about.php')),
    array('title' => '规则', 'url' => qf_url_page('page.php', array('slug' => 'rules'))),
    array('title' => '帮助', 'url' => qf_url_page('page.php', array('slug' => 'help'))),
);
$footer_nav_hidden_ids = array_filter(array_map('intval', explode(',', qf_setting('nav_hidden_forums', ''))));
if (!empty($footer_nav_hidden_ids)) {
    $footer_hidden_rs = mysqli_query(db(), "SELECT id,name FROM qf_forums WHERE id IN (" . implode(',', $footer_nav_hidden_ids) . ") ORDER BY display_order ASC, id ASC");
    while ($footer_hidden_rs && ($footer_hidden = mysqli_fetch_assoc($footer_hidden_rs))) {
        $footer_pages[] = array('title' => $footer_hidden['name'], 'url' => qf_url_forum(intval($footer_hidden['id'])));
    }
}
$footer_social_links = array(
    array('title' => 'GitHub', 'url' => 'https://github.com/gentpan/php.do', 'icon' => 'fa-brands fa-github'),
    array('title' => 'Issues', 'url' => 'https://github.com/gentpan/php.do/issues', 'icon' => 'fa-regular fa-circle-question'),
);
$footer_user = current_user();
$footer_icp = trim(qf_setting('icp_code', ''));
$online = qf_online_counts();
$online_today = qf_online_today_peak();
$online_members = qf_online_members(12);
?>
<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-top">
            <div class="site-footer-brand">
                <a class="site-footer-logo" href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="<?php echo h(qf_site_name()); ?>">
                    <img src="assets/logo-white.svg" alt="<?php echo h(qf_site_name()); ?>" draggable="false" oncontextmenu="return false;">
                </a>
                <p class="site-footer-desc"><?php echo h(qf_site_desc()); ?></p>
            </div>
            <div class="site-footer-social">
                <?php foreach ($footer_social_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener" title="<?php echo h($link['title']); ?>" aria-label="<?php echo h($link['title']); ?>">
                        <i class="<?php echo h($link['icon']); ?>" aria-hidden="true"></i>
                    </a>
                <?php } ?>
            </div>
        </div>
        <nav class="site-footer-links" aria-label="站点链接">
            <?php foreach ($footer_pages as $link) { ?>
                <a href="<?php echo h($link['url']); ?>"><?php echo h($link['title']); ?></a>
            <?php } ?>
        </nav>
        <?php if (!empty($footer_friend_links)) { ?>
            <nav class="site-footer-links site-footer-friends" aria-label="友情链接">
                <span class="site-footer-friends-label">友情链接</span>
                <?php foreach ($footer_friend_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
                <?php } ?>
            </nav>
        <?php } ?>
        <div class="site-footer-online" aria-label="在线状况">
            <div class="site-footer-online-stats">
                <span>当前在线 <b><?php echo intval($online['total']); ?></b></span>
                <span class="site-footer-online-sep">·</span>
                <span>会员 <b><?php echo intval($online['members']); ?></b></span>
                <span class="site-footer-online-sep">·</span>
                <span>访客 <b><?php echo intval($online['guests']); ?></b></span>
                <span class="site-footer-online-sep">·</span>
                <span>今日峰值 <b><?php echo intval($online_today['peak_total']); ?></b></span>
            </div>
            <?php if (!empty($online_members)) { ?>
                <div class="site-footer-online-members">
                    <span class="site-footer-online-label">在线会员</span>
                    <?php foreach ($online_members as $om) { ?>
                        <a href="<?php echo h(qf_url_user($om['id'])); ?>"><?php echo h(qf_user_display_name($om)); ?></a>
                    <?php } ?>
                    <?php if (intval($online['members']) > count($online_members)) { ?>
                        <span class="site-footer-online-more">等 <?php echo intval($online['members']); ?> 人</span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <div class="site-footer-bottom">
            <div class="site-footer-meta">
                <span class="sh-badge"><span class="sh-badge-k">Time</span><span class="sh-badge-v sh-badge-green"><?php echo number_format(qf_perf_seconds(), 3); ?>s</span></span>
                <span class="sh-badge"><span class="sh-badge-k">SQL</span><span class="sh-badge-v sh-badge-blue"><?php echo intval(qf_perf_sql_count()); ?></span></span>
                <span class="sh-badge"><span class="sh-badge-k">Online</span><span class="sh-badge-v sh-badge-orange"><?php echo intval($online['total']); ?></span></span>
                <?php if ($footer_icp !== '') { ?><span class="site-footer-icp"><?php echo nl2br(h($footer_icp)); ?></span><?php } ?>
            </div>
            <span class="site-footer-copy">&copy; <?php echo date('Y'); ?> <?php echo h(qf_site_name()); ?></span>
        </div>
    </div>
</footer>
<div class="qf-search-window" id="qf-search-modal" data-search-close>
    <div class="qf-search-window-box">
        <form method="get" action="<?php echo h(qf_url_page('search.php')); ?>" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input name="q" placeholder="搜索帖子、版块、分类…" autocomplete="off" aria-label="搜索">
            <kbd>Esc</kbd>
        </form>
        <div class="qf-search-window-hint">回车搜索 · <kbd>Esc</kbd> 关闭 · <kbd>⌘/Ctrl</kbd>&nbsp;<kbd>K</kbd> 或 <kbd>/</kbd> 打开</div>
    </div>
</div>
<aside class="phpdo-right-toolbar" aria-label="页面工具栏">
    <button type="button" data-scroll-top aria-label="返回顶部" data-tooltip="返回顶部"><i class="fa-solid fa-chevron-up" aria-hidden="true"></i></button>
    <a href="<?php echo h($footer_user ? qf_url_page('post.php') : qf_url_page('login.php')); ?>" aria-label="发帖" data-tooltip="发帖"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
    <button type="button" data-theme-toggle aria-label="主题" data-tooltip="切换主题"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
</aside>
<script src="assets/lib/litezoom.min.js"></script>
<script src="<?php echo h(qf_asset_js('main', 'assets/')); ?>"></script>
<script>
(function () {
    // Preline UI 初始化（页面加载 + AJAX 局部替换后）
    function qfPrelineInit() {
        if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
            window.HSStaticMethods.autoInit();
        }
    }
    if (document.readyState !== 'loading') { qfPrelineInit(); }
    window.addEventListener('load', qfPrelineInit);
    window.qfPrelineInit = qfPrelineInit;

    // 搜索模态窗：⌘/Ctrl+K 或 / 打开，Esc / 点击遮罩关闭
    (function () {
        var modal = document.getElementById('qf-search-modal');
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
        var icons = { system: 'fa-circle-half-stroke', light: 'fa-sun', dark: 'fa-moon' };
        var labels = { system: '跟随系统', light: '浅色', dark: '深色' };
        function pref() { try { return localStorage.getItem('qfThemeMode') || 'system'; } catch (e) { return 'system'; } }
        function systemDark() { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
        function apply(p) { document.body.classList.toggle('theme-php-dark', p === 'dark' || (p === 'system' && systemDark())); }
        function refresh(p) {
            var btns = document.querySelectorAll('[data-theme-toggle]');
            for (var i = 0; i < btns.length; i++) {
                var ic = btns[i].querySelector('i');
                if (ic) ic.className = 'fa-solid ' + icons[p];
                btns[i].setAttribute('title', '主题：' + labels[p] + '（点击切换）');
                btns[i].setAttribute('aria-label', '主题：' + labels[p]);
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
                try { localStorage.setItem('qfThemeMode', next); } catch (e) {}
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
            if (typeof window.qfSetLoading !== 'function') return;
            window.qfSetLoading(true, 'page');
            // 兜底：万一跳转最终没有发生，10 秒后自动清除加载层。
            window.setTimeout(function () {
                if (typeof window.qfSetLoading === 'function') window.qfSetLoading(false, 'page');
            }, 10000);
        }, 0);
    });
})();
</script>
<?php echo qf_setting('stats_code', ''); ?>
</body>
</html>
