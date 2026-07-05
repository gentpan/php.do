<?php
require_once __DIR__ . '/../functions.php';
$tag = clean_text(isset($_GET['tag']) ? $_GET['tag'] : '', 60);
if ($tag === '') {
    http_response_code(404);
    exit('标签不存在');
}
$page_title = $tag . ' - 标签 - ' . SITE_NAME;
qf_include_header();
$tag_sql = esc($tag);
$like_sql = esc($tag);
$threads = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar
    FROM qf_threads t
    LEFT JOIN qf_forums f ON f.id=t.forum_id
    LEFT JOIN qf_users u ON u.id=t.user_id
    WHERE t.is_deleted=0 AND (t.topic_category='{$tag_sql}' OR t.title LIKE '%{$like_sql}%' OR t.content LIKE '%{$like_sql}%')
    ORDER BY t.updated_at DESC
    LIMIT 50");
?>
<section class="card page-head">
    <div>
        <h1>#<?php echo h($tag); ?></h1>
        <p>标签路径：<?php echo h(qf_url_tag($tag)); ?></p>
    </div>
    <a class="btn" href="<?php echo h(qf_url_page('post.php')); ?>">发帖</a>
</section>

<section class="card thread-list">
    <?php $count = 0; while ($threads && ($t = mysqli_fetch_assoc($threads))) { $count++;
        $avatar = trim((string)$t['avatar']);
        if ($avatar === '') {
            $avatar = 'assets/avatar-default.svg';
        }
        $author = $t['nickname'] !== '' ? $t['nickname'] : $t['username'];
    ?>
        <div class="thread-row">
            <a class="phpdo-avatar" href="<?php echo h(qf_url_thread($t['id'])); ?>" aria-hidden="true" tabindex="-1">
                <img src="<?php echo h($avatar); ?>" alt="">
            </a>
            <div class="thread-main">
                <a class="thread-title" href="<?php echo h(qf_url_thread($t['id'])); ?>">
                    <?php if ($t['topic_category'] !== '') { ?><span class="category-tag"><?php echo h($t['topic_category']); ?></span><?php } ?>
                    <?php echo h($t['title']); ?>
                </a>
                <p><a class="phpdo-author-link" href="<?php echo h(qf_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a> · <?php echo h($t['forum_name']); ?> · <?php echo h(format_time($t['updated_at'])); ?></p>
            </div>
            <div class="thread-count">
                <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo qf_format_compact_number($t['replies']); ?></span>
                <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo qf_format_compact_number($t['views']); ?></span>
            </div>
        </div>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted phpdo-empty">暂无相关主题。</p><?php } ?>
</section>
<?php qf_include_footer(); ?>
