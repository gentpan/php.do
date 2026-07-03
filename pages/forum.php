<?php
require_once __DIR__ . '/../functions.php';
$fid = qf_path_id();
$frs = mysqli_query(db(), "SELECT * FROM qf_forums WHERE id={$fid} LIMIT 1");
$forum = $frs ? mysqli_fetch_assoc($frs) : null;
if (!$forum) {
    exit('版块不存在');
}
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if (!in_array($filter, array('all', 'new', 'good'))) {
    $filter = 'all';
}
$where_extra = '';
$order_sql = "(t.is_top>0) DESC, t.updated_at DESC";
if ($filter === 'new') {
    $order_sql = "(t.is_top>0) DESC, t.created_at DESC";
}
if ($filter === 'good') {
    $where_extra = " AND t.is_good=1";
    $order_sql = "(t.is_top>0) DESC, t.updated_at DESC";
}
$category = '';
if (qf_topic_category_enabled($fid)) {
    $candidate = clean_text(isset($_GET['category']) ? $_GET['category'] : '', 40);
    if (in_array($candidate, qf_topic_categories($fid))) {
        $category = $candidate;
        $category_sql = esc($category);
        $where_extra .= " AND topic_category='{$category_sql}'";
    }
}
$page_title = $forum['name'] . ' - ' . SITE_NAME;
qf_include_header();
$threads = mysqli_query(db(), "SELECT t.*, u.nickname FROM qf_threads t LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE t.forum_id={$fid} AND t.is_deleted=0{$where_extra}
    ORDER BY {$order_sql}
    LIMIT " . qf_forum_threads_limit());
?>
<section class="card page-head">
    <div>
        <h1><?php echo h($forum['name']); ?> <a class="back-home" href="<?php echo h(qf_url_page('index.php')); ?>">返回首页</a></h1>
        <p><?php echo h($forum['description']); ?></p>
    </div>
    <a class="btn" href="<?php echo h(qf_url_page('post.php', array('fid' => intval($forum['id'])))); ?>">发帖</a>
</section>
<nav class="filter-tabs">
    <a class="<?php if ($filter === 'all') echo 'active'; ?>" href="<?php echo h(qf_url_page('forum.php', array('id' => intval($fid), 'filter' => 'all'))); ?>">全部</a>
    <a class="<?php if ($filter === 'new') echo 'active'; ?>" href="<?php echo h(qf_url_page('forum.php', array('id' => intval($fid), 'filter' => 'new'))); ?>">新帖</a>
    <a class="<?php if ($filter === 'good') echo 'active'; ?>" href="<?php echo h(qf_url_page('forum.php', array('id' => intval($fid), 'filter' => 'good'))); ?>">精帖</a>
</nav>
<?php if (qf_topic_category_enabled($fid)) { ?>
    <nav class="filter-tabs category-tabs">
        <a class="<?php if ($category === '') echo 'active'; ?>" href="<?php echo h(qf_url_page('forum.php', array('id' => intval($fid), 'filter' => $filter))); ?>">全部分类</a>
        <?php foreach (qf_topic_categories($fid) as $cat) { ?>
            <a class="<?php if ($category === $cat) echo 'active'; ?>" href="<?php echo h(qf_url_page('forum.php', array('id' => intval($fid), 'filter' => $filter, 'category' => $cat))); ?>"><?php echo h($cat); ?></a>
        <?php } ?>
    </nav>
<?php } ?>
<section class="card thread-list">
    <?php while ($threads && $t = mysqli_fetch_assoc($threads)) { ?>
        <div class="thread-row">
            <div class="thread-main">
                <a class="thread-title" href="<?php echo h(qf_url_thread($t['id'])); ?>">
                    <?php if (intval($t['is_top']) === 1) { ?><span class="tag red">置顶</span><?php } ?>
                    <?php if (intval($t['is_top']) === 2) { ?><span class="tag red">置顶</span><?php } ?>
                    <?php if (intval($t['is_good'])) { ?><span class="tag blue">精华</span><?php } ?>
                    <?php if ($t['topic_category'] !== '') { ?><span class="category-tag"><?php echo h($t['topic_category']); ?></span><?php } ?>
                    <?php echo h($t['title']); ?>
                </a>
                <p><?php echo h($t['nickname']); ?> · <?php echo format_time($t['created_at']); ?></p>
            </div>
            <div class="thread-count"><?php echo intval($t['replies']); ?> 回复<br><?php echo intval($t['views']); ?> 浏览</div>
        </div>
    <?php } ?>
</section>
<?php qf_include_footer(); ?>
