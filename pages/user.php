<?php
require_once __DIR__ . '/../functions.php';
$me = current_user();
$id = pd_path_id();
$rs = mysqli_query(db(), "SELECT id,username,nickname,avatar,email,signature,coins,reply_count,points,group_id,created_at FROM pd_users WHERE id={$id} AND status=1 LIMIT 1");
$user = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$user) {
    http_response_code(404);
    exit('用户不存在');
}
$display_name = pd_user_display_name($user);
$avatar = pd_user_avatar($user, 200);
$user_points = intval(isset($user['points']) ? $user['points'] : 0);
$progress = pd_level_progress($user_points);
$group = pd_user_group($user);
$page_title = $display_name . ' - 用户主页 - ' . SITE_NAME;
pd_include_header();
$threads = mysqli_query(db(), "SELECT t.*, f.name AS forum_name FROM pd_threads t LEFT JOIN pd_forums f ON f.id=t.forum_id WHERE t.user_id=" . intval($user['id']) . " AND t.is_deleted=0 ORDER BY t.updated_at DESC LIMIT 30");
$posts = mysqli_query(db(), "SELECT p.*, t.title, t.id AS thread_id, t.is_good, t.is_top FROM pd_posts p LEFT JOIN pd_threads t ON t.id=p.thread_id WHERE p.user_id=" . intval($user['id']) . " AND p.is_deleted=0 AND t.is_deleted=0 ORDER BY p.created_at DESC LIMIT 20");
?>
<section class="card page-head pd-user-head">
    <img class="pd-author-avatar" src="<?php echo h($avatar); ?>" alt="">
    <div>
        <h1>
            <?php echo h($display_name); ?>
            <span class="pd-level">Lv.<?php echo intval($progress['level']); ?></span>
            <?php if ($group) { echo pd_user_group_badge_html($user); } ?>
        </h1>
        <p>@<?php echo h($user['username']); ?> · 注册于 <?php echo pd_time_html($user['created_at']); ?> · <?php echo intval($user['reply_count']); ?> 回复 · <?php echo $user_points; ?> 积分 · <?php echo intval($user['coins']); ?> 金币</p>
        <div class="pd-level-progress" aria-label="等级进度">
            <div class="pd-level-progress-meta">
                <span><?php echo h(pd_level_name($progress['level'])); ?></span>
                <?php if (!empty($progress['max'])) { ?>
                    <span>已满级</span>
                <?php } else { ?>
                    <span>距 Lv.<?php echo intval($progress['level'] + 1); ?> 还差 <?php echo intval($progress['remain']); ?> 分</span>
                <?php } ?>
            </div>
            <div class="pd-level-progress-track"><span style="width:<?php echo intval($progress['percent']); ?>%"></span></div>
        </div>
        <?php if (trim((string)$user['signature']) !== '') { ?><p><?php echo h($user['signature']); ?></p><?php } ?>
        <?php if ($me && intval($me['id']) !== intval($user['id'])) { ?>
            <div class="pd-user-actions">
                <a class="btn btn-solid pd-user-pm-btn" href="<?php echo h(pd_url_messages(0, intval($user['id']))); ?>"><i class="fa-regular fa-envelope" aria-hidden="true"></i> 发私信</a>
            </div>
        <?php } ?>
    </div>
</section>

<section class="card thread-list">
    <h2 class="pd-list-heading">TA 发布的主题</h2>
    <?php $count = 0; while ($threads && ($t = mysqli_fetch_assoc($threads))) { $count++; ?>
        <?php echo pd_render_thread_row($t, array('variant' => 'list', 'meta' => 'user', 'avatar_link' => false)); ?>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted pd-empty">暂无主题。</p><?php } ?>
</section>

<section class="card thread-list pd-user-replies">
    <h2 class="pd-list-heading">TA 最近的回复</h2>
    <?php $count = 0; while ($posts && ($p = mysqli_fetch_assoc($posts))) { $count++; ?>
        <div class="thread-row">
            <span class="pd-avatar" aria-hidden="true"><img src="<?php echo h($avatar); ?>" alt=""></span>
            <div class="thread-main">
                <a<?php echo pd_thread_title_attr($p, 'thread-title'); ?> href="<?php echo h(pd_url_thread($p['thread_id'])); ?>#replies"><?php echo h($p['title']); ?></a>
                <?php $excerpt = strip_tags($p['content']); $excerpt = function_exists('mb_substr') ? mb_substr($excerpt, 0, 80, 'UTF-8') : substr($excerpt, 0, 160); ?>
                <p><?php echo pd_time_html($p['created_at']); ?> · <?php echo h($excerpt); ?></p>
            </div>
        </div>
    <?php } ?>
    <?php if ($count === 0) { ?><p class="muted pd-empty">暂无回复。</p><?php } ?>
</section>
<?php pd_include_footer(); ?>
