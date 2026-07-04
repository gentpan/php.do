<?php
require_once __DIR__ . '/../functions.php';
$id = qf_path_id();
$rs = mysqli_query(db(), "SELECT id,username,nickname,avatar,signature,coins,reply_count,created_at FROM qf_users WHERE id={$id} AND status=1 LIMIT 1");
$user = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$user) {
    http_response_code(404);
    exit('用户不存在');
}
$display_name = $user['nickname'] !== '' ? $user['nickname'] : $user['username'];
$avatar = $user['avatar'] !== '' ? $user['avatar'] : 'assets/avatar-default.svg';
$page_title = $display_name . ' - 用户主页 - ' . SITE_NAME;
qf_include_header();
$threads = mysqli_query(db(), "SELECT t.*, f.name AS forum_name FROM qf_threads t LEFT JOIN qf_forums f ON f.id=t.forum_id WHERE t.user_id=" . intval($user['id']) . " AND t.is_deleted=0 ORDER BY t.updated_at DESC LIMIT 30");
$posts = mysqli_query(db(), "SELECT p.*, t.title, t.id AS thread_id FROM qf_posts p LEFT JOIN qf_threads t ON t.id=p.thread_id WHERE p.user_id=" . intval($user['id']) . " AND p.is_deleted=0 AND t.is_deleted=0 ORDER BY p.created_at DESC LIMIT 20");
?>
<section class="card page-head phpdo-user-head">
    <img class="phpdo-author-avatar" src="<?php echo h($avatar); ?>" alt="">
    <div>
        <h1><?php echo h($display_name); ?></h1>
        <p>@<?php echo h($user['username']); ?> · 注册于 <?php echo h(format_time($user['created_at'])); ?> · <?php echo intval($user['reply_count']); ?> 回复 · <?php echo intval($user['coins']); ?> 金币</p>
        <?php if (trim((string)$user['signature']) !== '') { ?><p><?php echo h($user['signature']); ?></p><?php } ?>
    </div>
</section>

<section class="card thread-list">
    <h2 class="phpdo-list-heading">TA 发布的主题</h2>
    <?php $count = 0; while ($threads && ($t = mysqli_fetch_assoc($threads))) { $count++; ?>
        <div class="thread-row">
            <div class="thread-main">
                <a class="thread-title" href="<?php echo h(qf_url_thread($t['id'])); ?>"><?php echo h($t['title']); ?></a>
                <p><?php echo h($t['forum_name']); ?> · <?php echo h(format_time($t['updated_at'])); ?></p>
            </div>
            <div class="thread-count">
                <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo intval($t['replies']); ?></span>
                <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo intval($t['views']); ?></span>
            </div>
        </div>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted phpdo-empty">暂无主题。</p><?php } ?>
</section>

<section class="card thread-list phpdo-user-replies">
    <h2 class="phpdo-list-heading">TA 最近的回复</h2>
    <?php $count = 0; while ($posts && ($p = mysqli_fetch_assoc($posts))) { $count++; ?>
        <div class="thread-row">
            <div class="thread-main">
                <a class="thread-title" href="<?php echo h(qf_url_thread($p['thread_id'])); ?>#replies"><?php echo h($p['title']); ?></a>
                <?php $excerpt = strip_tags($p['content']); $excerpt = function_exists('mb_substr') ? mb_substr($excerpt, 0, 80, 'UTF-8') : substr($excerpt, 0, 160); ?>
                <p><?php echo h(format_time($p['created_at'])); ?> · <?php echo h($excerpt); ?></p>
            </div>
        </div>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted phpdo-empty">暂无回复。</p><?php } ?>
</section>
<?php qf_include_footer(); ?>
