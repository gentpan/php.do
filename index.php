<?php
require_once __DIR__ . '/functions.php';
$legacy_admin_paths = array(
    'admin.php',
    'admin_action',
    'admin_action.php',
    'admin_ads',
    'admin_ads.php',
    'admin_cache',
    'admin_cache.php',
    'admin_navs',
    'admin_navs.php',
    'admin_security',
    'admin_security.php',
    'admin_settings',
    'admin_settings.php',
    'admin_users',
    'admin_users.php',
);
$request_path = trim(parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');
if (in_array($request_path, $legacy_admin_paths, true) || strpos($request_path, 'actions/') === 0) {
    http_response_code(404);
    exit('404 Not Found');
}
if (preg_match('#^user/([0-9]+)\.html$#', $request_path, $m)) {
    $_GET['id'] = intval($m[1]);
    $_SERVER['SCRIPT_NAME'] = '/user.php';
    require __DIR__ . '/pages/user.php';
    exit;
}
if (preg_match('#^tags/(.+)$#u', $request_path, $m)) {
    $_GET['tag'] = qf_tag_name_from_slug($m[1]);
    $_SERVER['SCRIPT_NAME'] = '/tags.php';
    require __DIR__ . '/pages/tags.php';
    exit;
}
if (preg_match('#^pages/([a-z0-9-]+)$#', $request_path, $m)) {
    $_GET['slug'] = $m[1];
    $_SERVER['SCRIPT_NAME'] = '/page.php';
    require __DIR__ . '/pages/page.php';
    exit;
}
$forum_slug_id = qf_forum_id_by_slug($request_path);
if ($forum_slug_id > 0) {
    $_GET['id'] = $forum_slug_id;
    $_SERVER['SCRIPT_NAME'] = '/forum.php';
    require __DIR__ . '/pages/forum.php';
    exit;
}
$front_routes = array(
    'download' => 'download.php',
    'edit-thread' => 'edit-thread.php',
    'login' => 'login.php',
    'move-thread' => 'move-thread.php',
    'notifications' => 'notifications.php',
    'post' => 'post.php',
    'profile' => 'profile.php',
    'settings' => 'profile.php',
    'rankings' => 'rankings.php',
    'register' => 'register.php',
    'search' => 'search.php',
    'tags' => 'tags.php',
    'user' => 'user.php',
);
if (isset($front_routes[$request_path])) {
    $_SERVER['SCRIPT_NAME'] = '/' . str_replace('-', '_', $front_routes[$request_path]);
    require __DIR__ . '/pages/' . $front_routes[$request_path];
    exit;
}
if ($request_path !== '') {
    http_response_code(404);
    exit('404 Not Found');
}

$page_title = SITE_NAME . ' - 首页';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'reply';
$allowed_filters = array('reply', 'latest', 'hot', 'good');
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'reply';
}

$filter_labels = array(
    'reply' => '最新回复',
    'latest' => '最新发表',
    'hot' => '最新热门',
    'good' => '最新精华',
);
$latest_where = "t.is_deleted=0 AND t.is_top<>2";
$latest_order = "t.is_top DESC, t.updated_at DESC";
if ($filter === 'latest') {
    $latest_order = "t.created_at DESC";
} elseif ($filter === 'hot') {
    $latest_order = "(t.views + t.replies * 80) DESC, t.updated_at DESC";
} elseif ($filter === 'good') {
    $latest_where .= " AND t.is_good=1";
    $latest_order = "t.updated_at DESC";
}

$forums = mysqli_query(db(), "SELECT f.*,
    (SELECT COUNT(*) FROM qf_threads t WHERE t.forum_id=f.id AND t.is_deleted=0) AS thread_count,
    (SELECT COUNT(*) FROM qf_posts p INNER JOIN qf_threads t2 ON p.thread_id=t2.id WHERE t2.forum_id=f.id AND p.is_deleted=0 AND t2.is_deleted=0) AS post_count
    FROM qf_forums f ORDER BY f.display_order ASC, f.id ASC");
$forum_rows = array();
while ($forums && ($forum = mysqli_fetch_assoc($forums))) {
    $forum_rows[] = $forum;
}

$latest = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar,
    (CASE WHEN t.content LIKE '%[img]%' OR EXISTS (SELECT 1 FROM qf_attachments a WHERE a.thread_id=t.id AND a.file_ext IN ('jpg','jpeg','png','gif','webp') LIMIT 1) THEN 1 ELSE 0 END) AS has_image
    FROM qf_threads t
    LEFT JOIN qf_forums f ON t.forum_id=f.id
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE {$latest_where}
    ORDER BY {$latest_order}
    LIMIT " . max(18, qf_home_threads_limit()));

$must_reads = array(
    'PHP 提问模板：版本、环境、日志、最小复现',
    '程序发布帖请补齐安装步骤、截图和许可证',
    'Composer 依赖升级前请先 review lock 文件',
    '线上排障优先查看 Nginx 与 PHP-FPM 日志',
    '安全讨论请避免公开真实密钥和隐私数据',
    '论坛支持 Markdown 格式与图片附件上传',
    '新用户先阅读社区规则和发帖分类说明',
    '开源项目更新欢迎同步 changelog 和仓库地址',
);

qf_include_header();
?>
<div class="phpdo-home-shell">
    <nav class="phpdo-category-bar" aria-label="论坛分类">
        <?php foreach ($forum_rows as $forum) { ?>
            <a href="<?php echo h(qf_url_forum($forum['id'])); ?>"><?php echo h($forum['name']); ?></a>
        <?php } ?>
    </nav>

    <?php echo qf_render_ad('top'); ?>

    <div class="phpdo-breadcrumb">
        <a href="<?php echo h(qf_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <span>导读</span>
        <span>›</span>
        <strong><?php echo h($filter_labels[$filter]); ?></strong>
    </div>

    <div class="phpdo-home-layout">
        <section class="phpdo-feed-card" aria-label="帖子列表">
            <div class="phpdo-feed-tabs">
                <?php foreach ($filter_labels as $key => $label) { ?>
                    <a class="<?php echo $filter === $key ? 'active' : ''; ?>" href="<?php echo h(qf_url_page('index.php')); ?>" data-feed-filter="<?php echo h($key); ?>"><?php echo h($label); ?></a>
                <?php } ?>
                <a class="phpdo-rss" href="<?php echo h(qf_url_page('index.php')); ?>" aria-label="订阅"><i class="fa-solid fa-square-rss" aria-hidden="true"></i><span>订阅</span></a>
            </div>
            <div class="phpdo-thread-list latest-list">
                <?php if ($latest && mysqli_num_rows($latest) > 0) { ?>
                    <?php while ($t = mysqli_fetch_assoc($latest)) {
                        $avatar = trim((string)$t['avatar']);
                        if ($avatar === '') {
                            $avatar = 'assets/avatar-default.svg';
                        }
                        $author = $t['nickname'] !== '' ? $t['nickname'] : $t['username'];
                        $is_new = strtotime($t['created_at']) >= time() - 86400 * 7;
                    ?>
                        <article class="phpdo-thread-row">
                            <a class="phpdo-avatar" href="<?php echo h(qf_url_thread($t['id'])); ?>" aria-hidden="true" tabindex="-1">
                                <img src="<?php echo h($avatar); ?>" alt="">
                            </a>
                            <div class="phpdo-thread-main">
                                <h2>
                                    <a href="<?php echo h(qf_url_thread($t['id'])); ?>">
                                        <?php if (intval($t['is_top']) === 1) { ?><span class="phpdo-pill phpdo-pill-outline">置顶</span><?php } ?>
                                        <?php echo h($t['title']); ?>
                                    </a>
                                    <?php if ($is_new) { ?><span class="phpdo-new">New</span><?php } ?>
                                    <?php if (intval($t['has_image'])) { ?><i class="fa-regular fa-image phpdo-image-icon" aria-hidden="true"></i><?php } ?>
                                </h2>
                                <p>
                                    <a class="phpdo-author-link" href="<?php echo h(qf_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a>
                                    <span>发表于 <?php echo h(format_time($t['created_at'])); ?></span>
                                    <?php if ($t['topic_category'] !== '') { ?><a class="phpdo-topic-tag" href="<?php echo h(qf_url_tag($t['topic_category'])); ?>"><?php echo h($t['topic_category']); ?></a><?php } ?>
                                    <?php if (intval($t['is_good'])) { ?><span class="phpdo-topic-tag phpdo-good">精华</span><?php } ?>
                                </p>
                            </div>
                            <div class="phpdo-thread-stats" aria-label="帖子统计">
                                <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo qf_format_compact_number($t['views']); ?></span>
                                <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo qf_format_compact_number($t['replies']); ?></span>
                            </div>
                        </article>
                    <?php } ?>
                <?php } else { ?>
                    <article class="phpdo-thread-row">
                        <div class="phpdo-thread-main">
                            <h2><a href="<?php echo h(qf_url_page('post.php')); ?>">还没有帖子，发布第一篇 PHP 技术讨论</a></h2>
                            <p><span><?php echo h(qf_site_name()); ?></span><span>等待新的讨论</span></p>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </section>

        <aside class="phpdo-home-sidebar" aria-label="侧边栏">
            <section class="phpdo-side-card phpdo-must-read">
                <h2><span></span>入站必看</h2>
                <ul>
                    <?php foreach ($must_reads as $item) { ?>
                        <li><?php echo h($item); ?></li>
                    <?php } ?>
                </ul>
            </section>
            <a class="phpdo-post-button" href="<?php echo h(qf_url_page('post.php')); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i>我要发帖</a>
            <section class="phpdo-ad phpdo-ad-warm">
                <strong>PHP 项目发布</strong>
                <span>开源程序 / 插件扩展 / 版本更新</span>
                <em>欢迎展示你的作品</em>
            </section>
            <section class="phpdo-ad phpdo-ad-dark">
                <strong>Composer 包推荐</strong>
                <span>稳定依赖 · 自动加载 · SDK 设计</span>
            </section>
            <section class="phpdo-ad phpdo-ad-cyan">
                <strong>部署与性能</strong>
                <span>PHP-FPM / Opcache / Redis / Nginx</span>
            </section>
            <?php echo qf_render_ad('sidebar'); ?>
        </aside>
    </div>
</div>
<?php echo qf_render_ad('footer'); ?>
<?php qf_include_footer(); ?>
