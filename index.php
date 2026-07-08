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

function qf_front_redirect($url) {
    $query = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_QUERY);
    if ($query !== null && $query !== '') {
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }
    header('Location: ' . $url, true, 301);
    exit;
}

if (preg_match('#^user/([0-9]+)\.html$#', $request_path, $m)) {
    $_GET['id'] = intval($m[1]);
    $_SERVER['SCRIPT_NAME'] = '/user.php';
    require __DIR__ . '/pages/user.php';
    exit;
}
if (preg_match('#^pages/([a-z0-9-]+)$#', $request_path, $m)) {
    qf_front_redirect(qf_url_page('page.php', array('slug' => $m[1])));
}
if (preg_match('#^([a-z0-9-]+)\.php$#', $request_path, $m)) {
    $static_pages = qf_static_pages();
    if (isset($static_pages[$m[1]])) {
        $_GET['slug'] = $m[1];
        $_SERVER['SCRIPT_NAME'] = '/page.php';
        require __DIR__ . '/pages/page.php';
        exit;
    }
}
$forum_slug_id = qf_forum_id_by_slug($request_path);
if ($forum_slug_id > 0) {
    $_GET['id'] = $forum_slug_id;
    $_SERVER['SCRIPT_NAME'] = '/forum.php';
    require __DIR__ . '/pages/forum.php';
    exit;
}
if ($request_path === 'feed' || $request_path === 'feed.php' || $request_path === 'rss') {
    $_SERVER['SCRIPT_NAME'] = '/feed.php';
    require __DIR__ . '/pages/feed.php';
    exit;
}
if ($request_path === 'about' || $request_path === 'about.php') {
    if ($request_path === 'about' && qf_rewrite_enabled()) {
        qf_front_redirect(qf_url_page('about.php'));
    }
    $_SERVER['SCRIPT_NAME'] = '/about.php';
    require __DIR__ . '/pages/about.php';
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
    'user' => 'user.php',
);
foreach ($front_routes as $front_path => $front_script) {
    $front_routes[$front_path . '.php'] = $front_script;
}
if (isset($front_routes[$request_path])) {
    if (substr($request_path, -4) !== '.php') {
        qf_front_redirect(qf_url_page($front_routes[$request_path]));
    }
    $_SERVER['SCRIPT_NAME'] = '/' . str_replace('-', '_', $front_routes[$request_path]);
    require __DIR__ . '/pages/' . $front_routes[$request_path];
    exit;
}
if ($request_path !== '') {
    http_response_code(404);
    exit('404 Not Found');
}

function phpdo_render_thread_row($t) {
    $avatar = qf_user_avatar($t, 80);
    $author = ($t['nickname'] !== null && $t['nickname'] !== '') ? $t['nickname'] : $t['username'];
    $is_new = strtotime($t['created_at']) >= time() - 86400 * 7;
    ob_start();
    ?>
    <article class="phpdo-thread-row">
        <a class="phpdo-avatar" href="<?php echo h(qf_url_thread($t['id'])); ?>" aria-hidden="true" tabindex="-1">
            <img src="<?php echo h($avatar); ?>" alt="">
        </a>
        <div class="phpdo-thread-main">
            <h2>
                <?php if (intval($t['is_top']) === 1) { ?><span class="phpdo-badge-sq phpdo-badge-top" title="置顶" aria-label="置顶"><i class="fa-solid fa-thumbtack" aria-hidden="true"></i></span><?php } ?>
                <?php if (intval($t['is_good'])) { ?><span class="phpdo-badge-sq phpdo-badge-good" title="精华" aria-label="精华"><i class="fa-solid fa-star" aria-hidden="true"></i></span><?php } ?>
                <a href="<?php echo h(qf_url_thread($t['id'])); ?>"<?php echo intval($t['is_good']) ? ' class="phpdo-title-good"' : ''; ?>><?php echo h($t['title']); ?></a>
                <?php if (intval($t['has_image'])) { ?><i class="fa-regular fa-image phpdo-image-icon" aria-hidden="true"></i><?php } ?>
                <?php if (!empty($t['has_attachment'])) { ?><i class="fa-solid fa-paperclip phpdo-attach-icon" title="含附件" aria-label="含附件"></i><?php } ?>
                <?php if ($is_new) { ?><i class="fa-solid fa-rectangle-new phpdo-new" title="新帖" aria-label="新帖"></i><?php } ?>
            </h2>
            <div class="phpdo-thread-meta">
                <p>
                    <a class="phpdo-author-link" href="<?php echo h(qf_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a>
                    <time class="phpdo-time" datetime="<?php echo h(qf_iso8601($t['created_at'])); ?>" title="<?php echo h($t['created_at']); ?>"><?php echo h(qf_time_ago($t['created_at'])); ?></time>
                    <?php if (!empty($t['forum_name'])) { ?><a class="phpdo-forum-tag phpdo-forum-tag-<?php echo intval($t['forum_id']) % 8; ?>" href="<?php echo h(qf_url_forum(intval($t['forum_id']))); ?>"><?php echo h($t['forum_name']); ?></a><?php } ?>
                </p>
                <div class="phpdo-thread-stats" aria-label="帖子统计">
                    <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo qf_format_compact_number($t['views']); ?></span>
                    <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo qf_format_compact_number($t['replies']); ?></span>
                </div>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
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
$latest_ts_col = "t.updated_at";
if ($filter === 'latest') {
    $latest_order = "t.created_at DESC";
    $latest_ts_col = "t.created_at";
} elseif ($filter === 'hot') {
    $latest_order = "(t.views + t.replies * 80) DESC, t.updated_at DESC";
} elseif ($filter === 'good') {
    $latest_where .= " AND t.is_good=1";
    $latest_order = "t.updated_at DESC";
}

$phpdo_per_page = 20;
$phpdo_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$phpdo_ajax = isset($_GET['ajax']) ? $_GET['ajax'] : '';

$phpdo_thread_select = "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.email,
    (CASE WHEN t.content LIKE '%[img]%' OR EXISTS (SELECT 1 FROM qf_attachments a WHERE a.thread_id=t.id AND a.file_ext IN ('jpg','jpeg','png','gif','webp') LIMIT 1) THEN 1 ELSE 0 END) AS has_image,
    (CASE WHEN EXISTS (SELECT 1 FROM qf_attachments a2 WHERE a2.thread_id=t.id AND a2.file_ext NOT IN ('jpg','jpeg','png','gif','webp') LIMIT 1) THEN 1 ELSE 0 END) AS has_attachment
    FROM qf_threads t
    LEFT JOIN qf_forums f ON t.forum_id=f.id
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE {$latest_where}
    ORDER BY {$latest_order}";

// AJAX：轮询是否有新增/更新的话题
if ($phpdo_ajax === 'check') {
    $since = isset($_GET['since']) ? trim((string)$_GET['since']) : '';
    $count = 0;
    if ($since !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since)) {
        $since_sql = esc($since);
        $cr = mysqli_query(db(), "SELECT COUNT(*) AS c FROM qf_threads t WHERE {$latest_where} AND {$latest_ts_col} > '{$since_sql}'");
        $crow = $cr ? mysqli_fetch_assoc($cr) : null;
        $count = $crow ? intval($crow['c']) : 0;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('count' => $count));
    exit;
}

// AJAX：分页加载更多帖子行
if ($phpdo_ajax === 'rows') {
    $offset = ($phpdo_page - 1) * $phpdo_per_page;
    $rs = mysqli_query(db(), $phpdo_thread_select . " LIMIT " . ($phpdo_per_page + 1) . " OFFSET " . intval($offset));
    $rows = array();
    while ($rs && ($t = mysqli_fetch_assoc($rs))) { $rows[] = $t; }
    $has_more = count($rows) > $phpdo_per_page;
    if ($has_more) { array_pop($rows); }
    header('Content-Type: text/html; charset=utf-8');
    header('X-Has-More: ' . ($has_more ? '1' : '0'));
    foreach ($rows as $t) { echo phpdo_render_thread_row($t); }
    exit;
}

$forums = mysqli_query(db(), "SELECT f.*,
    (SELECT COUNT(*) FROM qf_threads t WHERE t.forum_id=f.id AND t.is_deleted=0) AS thread_count,
    (SELECT COUNT(*) FROM qf_posts p INNER JOIN qf_threads t2 ON p.thread_id=t2.id WHERE t2.forum_id=f.id AND p.is_deleted=0 AND t2.is_deleted=0) AS post_count
    FROM qf_forums f ORDER BY f.display_order ASC, f.id ASC");
$forum_rows = array();
while ($forums && ($forum = mysqli_fetch_assoc($forums))) {
    $forum_rows[] = $forum;
}

$latest_rs = mysqli_query(db(), $phpdo_thread_select . " LIMIT " . ($phpdo_per_page + 1));
$latest_rows = array();
while ($latest_rs && ($t = mysqli_fetch_assoc($latest_rs))) { $latest_rows[] = $t; }
$phpdo_has_more = count($latest_rows) > $phpdo_per_page;
if ($phpdo_has_more) { array_pop($latest_rows); }

$maxr = mysqli_query(db(), "SELECT MAX({$latest_ts_col}) AS m FROM qf_threads t WHERE {$latest_where}");
$phpdo_latest_ts = ($maxr && ($mr = mysqli_fetch_assoc($maxr))) ? (string)$mr['m'] : '';

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

$community_stats = qf_community_stats();
$latest_users = qf_latest_users(8);

qf_include_header();
?>
<div class="phpdo-home-shell">
    <?php echo qf_render_ad('top'); ?>

    <div class="phpdo-home-layout">
        <section class="phpdo-feed-card" aria-label="帖子列表">
            <div class="phpdo-feed-tabs">
                <?php foreach ($filter_labels as $key => $label) { ?>
                    <a class="<?php echo $filter === $key ? 'active' : ''; ?>" href="<?php echo h(qf_url_page('index.php')); ?>" data-feed-filter="<?php echo h($key); ?>"><?php echo h($label); ?></a>
                <?php } ?>
            </div>
            <button type="button" class="phpdo-new-topics" data-new-topics hidden>
                <i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"></i>
                查看 <b data-new-count>0</b> 个新的或更新的话题
            </button>
            <div class="phpdo-thread-list latest-list" data-feed-list data-filter="<?php echo h($filter); ?>" data-per-page="<?php echo intval($phpdo_per_page); ?>" data-has-more="<?php echo $phpdo_has_more ? '1' : '0'; ?>" data-latest-ts="<?php echo h($phpdo_latest_ts); ?>">
                <?php if (!empty($latest_rows)) { ?>
                    <?php foreach ($latest_rows as $t) { echo phpdo_render_thread_row($t); } ?>
                <?php } else { ?>
                    <article class="phpdo-thread-row">
                        <div class="phpdo-thread-main">
                            <h2><a href="<?php echo h(qf_url_page('post.php')); ?>">还没有帖子，发布第一篇 PHP 技术讨论</a></h2>
                            <p><span><?php echo h(qf_site_name()); ?></span><span>等待新的讨论</span></p>
                        </div>
                    </article>
                <?php } ?>
            </div>
            <div class="phpdo-feed-more" data-feed-more>
                <button type="button" class="phpdo-more-btn" data-load-more<?php echo $phpdo_has_more ? '' : ' hidden'; ?>>
                    <span class="phpdo-more-label">加载更多</span>
                    <span class="phpdo-more-spin" aria-hidden="true"></span>
                </button>
                <div class="phpdo-feed-end" data-feed-end<?php echo (!empty($latest_rows) && !$phpdo_has_more) ? '' : ' hidden'; ?>>没有更多话题了</div>
            </div>
        </section>

        <aside class="phpdo-home-sidebar" aria-label="侧边栏">
            <a class="phpdo-post-button" href="<?php echo h(qf_url_page('post.php')); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i>我要发帖</a>
            <section class="phpdo-side-card phpdo-must-read">
                <h2><span></span>入站必看</h2>
                <ul>
                    <?php foreach ($must_reads as $item) { ?>
                        <li><?php echo h($item); ?></li>
                    <?php } ?>
                </ul>
            </section>
            <section class="phpdo-side-card phpdo-community-card">
                <h2><span></span>关于社区</h2>
                <p class="phpdo-community-slogan"><?php echo h(qf_site_slogan()); ?></p>
                <div class="phpdo-community-stats">
                    <div><b><?php echo qf_format_compact_number($community_stats['members']); ?></b><span>成员</span></div>
                    <div><b><?php echo qf_format_compact_number($community_stats['topics_7d']); ?></b><span>近7天话题</span></div>
                    <div><b><?php echo qf_format_compact_number($community_stats['active_7d']); ?></b><span>近7天活跃</span></div>
                </div>
                <a class="phpdo-community-link" href="<?php echo h(qf_url_page('about.php')); ?>">了解本站 <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
            </section>
            <?php if (!empty($latest_users)) { ?>
            <section class="phpdo-side-card phpdo-newuser-card">
                <h2><span></span>用户数目</h2>
                <p class="phpdo-newuser-total">目前论坛共有 <b><?php echo qf_format_compact_number($community_stats['members']); ?></b> 位<?php echo h(qf_member_noun()); ?></p>
                <h3 class="phpdo-newuser-sub">欢迎新用户</h3>
                <div class="phpdo-newuser-grid">
                    <?php foreach ($latest_users as $nu) {
                        $nu_name = ($nu['nickname'] !== null && $nu['nickname'] !== '') ? $nu['nickname'] : $nu['username'];
                    ?>
                        <a class="phpdo-newuser" href="<?php echo h(qf_url_user($nu['id'])); ?>" title="<?php echo h($nu_name); ?>">
                            <img src="<?php echo h(qf_user_avatar($nu, 96)); ?>" alt="" loading="lazy">
                            <span><?php echo h($nu_name); ?></span>
                        </a>
                    <?php } ?>
                </div>
            </section>
            <?php } ?>
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
