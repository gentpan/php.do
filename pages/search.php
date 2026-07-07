<?php
require_once __DIR__ . '/../functions.php';
$q = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
$page_title = '搜索结果 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <h1>搜索结果</h1>
    <form class="search" method="get">
        <input name="q" value="<?php echo h($q); ?>" placeholder="输入关键词">
        <button class="btn">搜索</button>
    </form>
</section>
<?php if ($q !== '') { 
    $qs = esc($q);
    $rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.email
        FROM qf_threads t
        LEFT JOIN qf_forums f ON t.forum_id=f.id
        LEFT JOIN qf_users u ON t.user_id=u.id
        WHERE t.is_deleted=0 AND (t.title LIKE '%{$qs}%' OR t.content LIKE '%{$qs}%' OR t.topic_category LIKE '%{$qs}%')
        ORDER BY t.updated_at DESC LIMIT 50");
    $found = $rs ? mysqli_num_rows($rs) : 0;
?>
<section class="card thread-list">
    <?php if ($found === 0) { ?>
        <div class="phpdo-empty">没有找到相关帖子，可以换一个关键词再试。</div>
    <?php } ?>
    <?php while ($rs && $t = mysqli_fetch_assoc($rs)) {
        $avatar = qf_user_avatar($t, 80);
        $author = $t['nickname'] !== '' ? $t['nickname'] : $t['username'];
    ?>
        <div class="thread-row">
            <a class="phpdo-avatar" href="<?php echo h(qf_url_thread($t['id'])); ?>" aria-hidden="true" tabindex="-1"><img src="<?php echo h($avatar); ?>" alt=""></a>
            <div class="thread-main">
                <a class="thread-title" href="<?php echo h(qf_url_thread($t['id'])); ?>"><?php echo h($t['title']); ?></a>
                <p>
                    <a class="phpdo-author-link" href="<?php echo h(qf_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a>
                    <span><?php echo h($t['forum_name']); ?></span>
                    <span><?php echo format_time($t['updated_at']); ?></span>
                    <?php if ($t['topic_category'] !== '') { ?><a class="phpdo-topic-tag" href="<?php echo h(qf_url_category(intval($t['forum_id']), $t['topic_category'])); ?>"><?php echo h($t['topic_category']); ?></a><?php } ?>
                </p>
            </div>
            <div class="thread-count"><span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo qf_format_compact_number($t['views']); ?></span><span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo qf_format_compact_number($t['replies']); ?></span></div>
        </div>
    <?php } ?>
</section>
<?php } ?>
<?php qf_include_footer(); ?>
