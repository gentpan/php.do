</main>
<?php
$footer_friend_links = array();
if (qf_friend_links_enabled()) {
    $footer_friend_links = qf_friend_links();
}
$footer_forums = array();
$footer_forum_rs = mysqli_query(db(), "SELECT id,name FROM qf_forums ORDER BY display_order ASC, id ASC LIMIT 6");
while ($footer_forum_rs && ($footer_forum = mysqli_fetch_assoc($footer_forum_rs))) {
    $footer_forums[] = $footer_forum;
}
$footer_pages = array(
    array('title' => '首页', 'url' => qf_url_page('index.php')),
    array('title' => '搜索', 'url' => qf_url_page('search.php')),
    array('title' => '关于', 'url' => qf_url_page('page.php', array('slug' => 'about'))),
    array('title' => '规则', 'url' => qf_url_page('page.php', array('slug' => 'rules'))),
    array('title' => '帮助', 'url' => qf_url_page('page.php', array('slug' => 'help'))),
);
$footer_social_links = array(
    array('title' => 'GitHub', 'url' => 'https://github.com/gentpan/php.do', 'icon' => 'fa-brands fa-github'),
    array('title' => 'Issues', 'url' => 'https://github.com/gentpan/php.do/issues', 'icon' => 'fa-regular fa-circle-question'),
);
$footer_user = current_user();
$footer_icp = trim(qf_setting('icp_code', ''));
?>
<footer class="footer">
    <div class="wrap footer-panel">
        <section class="footer-brand" aria-label="站点信息">
            <a class="footer-logo" href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="<?php echo h(qf_site_name()); ?>">
                <img src="assets/logo.svg" alt="<?php echo h(qf_site_name()); ?>">
            </a>
            <p><?php echo h(qf_site_desc()); ?></p>
            <div class="footer-copyright">
                <span>&copy; <?php echo date('Y'); ?> <?php echo h(qf_site_name()); ?></span>
                <?php if ($footer_icp !== '') { ?><span><?php echo nl2br(h($footer_icp)); ?></span><?php } ?>
            </div>
        </section>

        <div class="footer-link-groups">
            <nav class="footer-link-group footer-site-nav" aria-label="站点链接">
                <h2>站点</h2>
                <?php foreach ($footer_pages as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>"><?php echo h($link['title']); ?></a>
                <?php } ?>
            </nav>
            <nav class="footer-link-group footer-forum-nav" aria-label="论坛分类">
                <h2>分类</h2>
                <?php foreach ($footer_forums as $footer_forum) { ?>
                    <a href="<?php echo h(qf_url_forum($footer_forum['id'])); ?>"><?php echo h($footer_forum['name']); ?></a>
                <?php } ?>
            </nav>
            <?php if (!empty($footer_friend_links)) { ?>
                <nav class="footer-link-group footer-external-links" aria-label="友情链接">
                    <h2>链接</h2>
                    <?php foreach ($footer_friend_links as $link) { ?>
                        <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
                    <?php } ?>
                </nav>
            <?php } ?>
        </div>

        <section class="footer-social" aria-label="社交链接">
            <div class="footer-social-links">
                <?php foreach ($footer_social_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo h($link['title']); ?>">
                        <i class="<?php echo h($link['icon']); ?>" aria-hidden="true"></i>
                        <span><?php echo h($link['title']); ?></span>
                    </a>
                <?php } ?>
            </div>
            <div class="footer-qr" aria-label="社区二维码位置">
                <span><i class="fa-solid fa-qrcode" aria-hidden="true"></i></span>
                <strong>社区二维码</strong>
            </div>
        </section>
    </div>
</footer>
<aside class="phpdo-right-toolbar" aria-label="页面工具栏">
    <a href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="首页" title="首页"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
    <a href="<?php echo h(qf_url_page('search.php')); ?>" aria-label="搜索" title="搜索"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></a>
    <a href="<?php echo h($footer_user ? qf_url_page('post.php') : qf_url_page('login.php')); ?>" aria-label="发帖" title="发帖"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
    <button type="button" data-scroll-top aria-label="返回顶部" title="返回顶部"><i class="fa-solid fa-chevron-up" aria-hidden="true"></i></button>
    <button type="button" data-scroll-bottom aria-label="到底部" title="到底部"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
</aside>
<script src="assets/litezoom.min.js"></script>
<script src="<?php echo h(qf_asset_js('app')); ?>"></script>
<script>
(function () {
    var toggle = document.querySelector('[data-theme-toggle]');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var dark = document.body.classList.toggle('theme-php-dark');
            try { localStorage.setItem('qfThemeMode', dark ? 'dark' : 'light'); } catch (e) {}
        });
    }

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
            window.qfSetLoading(true);
            // 兜底：万一跳转最终没有发生，10 秒后自动清除加载层。
            window.setTimeout(function () {
                if (typeof window.qfSetLoading === 'function') window.qfSetLoading(false);
            }, 10000);
        }, 0);
    });
})();
</script>
<?php echo qf_setting('stats_code', ''); ?>
</body>
</html>
