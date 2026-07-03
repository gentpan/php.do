<?php
require_once __DIR__ . '/db.php';
$page_title = SITE_NAME . ' - 首页';
include __DIR__ . '/header.php';
$forums = mysqli_query(db(), "SELECT f.*, 
    (SELECT COUNT(*) FROM qf_threads t WHERE t.forum_id=f.id AND t.is_deleted=0) AS thread_count,
    (SELECT COUNT(*) FROM qf_posts p INNER JOIN qf_threads t2 ON p.thread_id=t2.id WHERE t2.forum_id=f.id AND p.is_deleted=0 AND t2.is_deleted=0) AS post_count
    FROM qf_forums f ORDER BY f.display_order ASC, f.id ASC");
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$latest_title = '最新帖子';
$latest_where = "t.is_deleted=0 AND t.is_top<>2";
$latest_order = "t.is_top DESC, t.updated_at DESC";
if ($filter === 'latest') {
    $latest_title = '最新发帖';
    $latest_order = "t.created_at DESC";
} elseif ($filter === 'good') {
    $latest_title = '精华帖子';
    $latest_where = "t.is_deleted=0 AND t.is_top<>2 AND t.is_good=1";
    $latest_order = "t.updated_at DESC";
}
$latest = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname,
    (CASE WHEN t.content LIKE '%[img]%' OR EXISTS (SELECT 1 FROM qf_attachments a WHERE a.thread_id=t.id AND a.file_ext IN ('jpg','jpeg','png','gif','webp') LIMIT 1) THEN 1 ELSE 0 END) AS has_image
    FROM qf_threads t
    LEFT JOIN qf_forums f ON t.forum_id=f.id
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE {$latest_where}
    ORDER BY {$latest_order}
    LIMIT " . qf_home_threads_limit());
?>
<?php echo qf_render_ad('top'); ?>
<section class="hero card">
    <div>
        <p><?php echo h(qf_site_desc()); ?></p>
    </div>
    <a class="btn" href="<?php echo h(qf_url_page('post.php')); ?>">发布新帖</a>
</section>
<div class="grid">
    <section>
        <div class="latest-title-menu">
            <div class="latest-title-filter">
                <h2 class="section-title latest-title-trigger"><?php echo h($latest_title); ?></h2>
                <div class="latest-title-dropdown">
                    <a href="<?php echo h(qf_url_page('index.php', array('filter' => 'latest'))); ?>">最新发帖</a>
                    <a href="<?php echo h(qf_url_page('index.php', array('filter' => 'good'))); ?>">精华帖子</a>
                </div>
            </div>
            <a class="btn btn-small mobile-post-btn" href="<?php echo h(qf_url_page('post.php')); ?>">发帖</a>
        </div>
        <div class="card list latest-list">
            <?php while ($latest && $t = mysqli_fetch_assoc($latest)) { ?>
                <div class="list-row">
                    <div class="list-main">
                        <a href="<?php echo h(qf_url_thread($t['id'])); ?>">
                            <?php if (intval($t['is_top']) === 1) { ?><span class="tag red">置顶</span><?php } ?>
                            <?php if (intval($t['is_good'])) { ?><span class="tag blue">精华</span><?php } ?>
                            <?php if ($t['topic_category'] !== '') { ?><span class="category-tag"><?php echo h($t['topic_category']); ?></span><?php } ?>
                            <?php echo h($t['title']); ?>
                            <?php if (intval($t['has_image'])) { ?><span class="image-badge">图</span><?php } ?>
                        </a>
                        <p class="list-meta">
                            <span class="meta-text"><?php echo h($t['forum_name']); ?> · <?php echo h($t['nickname']); ?> · <?php echo format_time($t['updated_at']); ?></span>
                            <span class="meta-metrics">
                                <span title="浏览数" class="metric metric-view">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 12s3.6-6.2 9.8-6.2S21.8 12 21.8 12s-3.6 6.2-9.8 6.2S2.2 12 2.2 12z"></path><circle cx="12" cy="12" r="2.8"></circle></svg>
                                    <?php echo intval($t['views']); ?>
                                </span>
                                <span title="回帖数" class="metric metric-reply">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5h14a2 2 0 0 1 2 2v6.8a2 2 0 0 1-2 2H9.6L5.4 20v-2.7H5a2 2 0 0 1-2-2V8.5a2 2 0 0 1 2-2z"></path></svg>
                                    <?php echo intval($t['replies']); ?>
                                </span>
                            </span>
                        </p>
                    </div>
                    <div class="list-metrics">
                        <span title="浏览数" class="metric metric-view">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 12s3.6-6.2 9.8-6.2S21.8 12 21.8 12s-3.6 6.2-9.8 6.2S2.2 12 2.2 12z"></path><circle cx="12" cy="12" r="2.8"></circle></svg>
                            <?php echo intval($t['views']); ?>
                        </span>
                        <span title="回帖数" class="metric metric-reply">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5h14a2 2 0 0 1 2 2v6.8a2 2 0 0 1-2 2H9.6L5.4 20v-2.7H5a2 2 0 0 1-2-2V8.5a2 2 0 0 1 2-2z"></path></svg>
                            <?php echo intval($t['replies']); ?>
                        </span>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>
    <aside>
        <h2 class="section-title">论坛版块</h2>
        <?php echo qf_render_ad('sidebar'); ?>
        <?php while ($forums && $f = mysqli_fetch_assoc($forums)) { ?>
            <a class="forum-card card side-forum-card" href="<?php echo h(qf_url_forum($f['id'])); ?>">
                <div>
                    <h3><?php echo h($f['name']); ?></h3>
                    <p><?php echo h($f['description']); ?></p>
                </div>
                <div class="stats">
                    <strong><?php echo intval($f['thread_count']); ?></strong>主题
                    <strong><?php echo intval($f['post_count']); ?></strong>回复
                </div>
            </a>
        <?php } ?>
    </aside>
</div>
<?php echo qf_render_ad('footer'); ?>
<?php include __DIR__ . '/footer.php'; ?>
