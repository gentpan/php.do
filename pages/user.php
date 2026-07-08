<?php
require_once __DIR__ . '/../functions.php';
$id = qf_path_id();
$rs = mysqli_query(db(), "SELECT id,username,nickname,avatar,email,signature,coins,reply_count,points,created_at FROM qf_users WHERE id={$id} AND status=1 LIMIT 1");
$user = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$user) {
    http_response_code(404);
    exit('用户不存在');
}
$display_name = qf_user_display_name($user);
$avatar = qf_user_avatar($user, 200);
$page_title = $display_name . ' - 用户主页 - ' . SITE_NAME;
qf_include_header();
$threads = mysqli_query(db(), "SELECT t.*, f.name AS forum_name FROM qf_threads t LEFT JOIN qf_forums f ON f.id=t.forum_id WHERE t.user_id=" . intval($user['id']) . " AND t.is_deleted=0 ORDER BY t.updated_at DESC LIMIT 30");
$posts = mysqli_query(db(), "SELECT p.*, t.title, t.id AS thread_id, t.is_good, t.is_top FROM qf_posts p LEFT JOIN qf_threads t ON t.id=p.thread_id WHERE p.user_id=" . intval($user['id']) . " AND p.is_deleted=0 AND t.is_deleted=0 ORDER BY p.created_at DESC LIMIT 20");
?>
<section class="card page-head phpdo-user-head">
    <img class="phpdo-author-avatar" src="<?php echo h($avatar); ?>" alt="">
    <div>
        <?php $user_points = intval(isset($user['points']) ? $user['points'] : 0); ?>
        <h1><?php echo h($display_name); ?> <span class="phpdo-level">Lv.<?php echo intval(qf_user_level($user_points)); ?></span></h1>
        <p>@<?php echo h($user['username']); ?> · 注册于 <?php echo qf_time_html($user['created_at']); ?> · <?php echo intval($user['reply_count']); ?> 回复 · <?php echo $user_points; ?> 积分 · <?php echo intval($user['coins']); ?> 金币</p>
        <?php if (trim((string)$user['signature']) !== '') { ?><p><?php echo h($user['signature']); ?></p><?php } ?>
    </div>
</section>

<section class="card thread-list">
    <h2 class="phpdo-list-heading">TA 发布的主题</h2>
    <?php $count = 0; while ($threads && ($t = mysqli_fetch_assoc($threads))) { $count++; ?>
        <?php echo qf_render_thread_row($t, array('variant' => 'list', 'meta' => 'user', 'avatar_link' => false)); ?>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted phpdo-empty">暂无主题。</p><?php } ?>
</section>

<section class="card thread-list phpdo-user-replies">
    <h2 class="phpdo-list-heading">TA 最近的回复</h2>
    <?php $count = 0; while ($posts && ($p = mysqli_fetch_assoc($posts))) { $count++; ?>
        <div class="thread-row">
            <span class="phpdo-avatar" aria-hidden="true"><img src="<?php echo h($avatar); ?>" alt=""></span>
            <div class="thread-main">
                <a<?php echo qf_thread_title_attr($p, 'thread-title'); ?> href="<?php echo h(qf_url_thread($p['thread_id'])); ?>#replies"><?php echo h($p['title']); ?></a>
                <?php $excerpt = strip_tags($p['content']); $excerpt = function_exists('mb_substr') ? mb_substr($excerpt, 0, 80, 'UTF-8') : substr($excerpt, 0, 160); ?>
                <p><?php echo qf_time_html($p['created_at']); ?> · <?php echo h($excerpt); ?></p>
            </div>
        </div>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted phpdo-empty">暂无回复。</p><?php } ?>
</section>
<?php qf_include_footer(); ?>
