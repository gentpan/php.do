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
$footer_user = current_user();
?>
<footer class="footer">
    <div class="wrap footer-links">
        <nav class="footer-page-nav" aria-label="页脚导航">
            <?php foreach ($footer_pages as $link) { ?>
                <a href="<?php echo h($link['url']); ?>"><?php echo h($link['title']); ?></a>
            <?php } ?>
            <?php foreach ($footer_forums as $footer_forum) { ?>
                <a href="<?php echo h(qf_url_forum($footer_forum['id'])); ?>"><?php echo h($footer_forum['name']); ?></a>
            <?php } ?>
        </nav>
        <?php if (!empty($footer_friend_links)) { ?>
            <nav class="footer-friend-links" aria-label="友情链接">
                <?php foreach ($footer_friend_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
                <?php } ?>
            </nav>
        <?php } ?>
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
<?php echo qf_setting('stats_code', ''); ?>
</body>
</html>
