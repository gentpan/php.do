<?php
require_once __DIR__ . '/../functions.php';
$me = current_user();
$id = pd_path_id();
$rs = mysqli_query(db(), "SELECT id,username,nickname,avatar,email,email_bound_at,signature,gender,coins,reply_count,points,group_id,is_admin,is_moderator,created_at FROM pd_users WHERE id={$id} AND status=1 LIMIT 1");
$user = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$user) {
    http_response_code(404);
    exit('用户不存在');
}
$uid = intval($user['id']);
$display_name = pd_user_display_name($user);
$avatar = pd_user_avatar($user, 200);
$user_points = intval(isset($user['points']) ? $user['points'] : 0);
$progress = pd_level_progress($user_points);
$group = pd_user_group($user);
$is_self = $me && intval($me['id']) === $uid;

// ===== 统计 =====
$stat = array('threads' => 0, 'posts' => 0, 'views' => 0, 'good' => 0, 'likes' => 0, 'signins' => 0);
$r = mysqli_query(db(), "SELECT COUNT(*) c, COALESCE(SUM(views),0) v, COALESCE(SUM(is_good),0) g FROM pd_threads WHERE user_id={$uid} AND is_deleted=0");
if ($r && ($x = mysqli_fetch_assoc($r))) {
    $stat['threads'] = intval($x['c']);
    $stat['views'] = intval($x['v']);
    $stat['good'] = intval($x['g']);
}
$r = mysqli_query(db(), "SELECT COUNT(*) c FROM pd_posts WHERE user_id={$uid} AND is_deleted=0");
if ($r && ($x = mysqli_fetch_assoc($r))) { $stat['posts'] = intval($x['c']); }
$r = mysqli_query(db(), "SELECT
        (SELECT COUNT(*) FROM pd_thread_votes v INNER JOIN pd_threads t ON t.id=v.thread_id WHERE t.user_id={$uid} AND v.vote=1) +
        (SELECT COUNT(*) FROM pd_post_votes pv INNER JOIN pd_posts p ON p.id=pv.post_id WHERE p.user_id={$uid} AND pv.vote=1) AS likes");
if ($r && ($x = mysqli_fetch_assoc($r))) { $stat['likes'] = intval($x['likes']); }
$r = mysqli_query(db(), "SELECT COUNT(*) c FROM pd_signins WHERE user_id={$uid}");
if ($r && ($x = mysqli_fetch_assoc($r))) { $stat['signins'] = intval($x['c']); }
$last_seen = '';
$r = mysqli_query(db(), "SELECT MAX(last_seen) s FROM pd_online WHERE user_id={$uid}");
if ($r && ($x = mysqli_fetch_assoc($r))) { $last_seen = (string)$x['s']; }

$threads = mysqli_query(db(), "SELECT t.*, f.name AS forum_name FROM pd_threads t LEFT JOIN pd_forums f ON f.id=t.forum_id WHERE t.user_id={$uid} AND t.is_deleted=0 ORDER BY t.updated_at DESC LIMIT 30");
$posts = mysqli_query(db(), "SELECT p.*, t.title, t.id AS thread_id, t.is_good, t.is_top FROM pd_posts p LEFT JOIN pd_threads t ON t.id=p.thread_id WHERE p.user_id={$uid} AND p.is_deleted=0 AND t.is_deleted=0 ORDER BY p.created_at DESC LIMIT 30");

$page_title = $display_name . ' - 个人中心 - ' . SITE_NAME;
pd_include_header();
?>
<div class="pd-profile">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong><?php echo h($display_name); ?></strong>
    </div>

    <section class="pd-profile-hero">
        <img class="pd-profile-avatar" src="<?php echo h($avatar); ?>" alt="">
        <div class="pd-profile-main">
            <div class="pd-profile-name">
                <h1><?php echo h($display_name); ?></h1>
                <span class="pd-level">Lv.<?php echo intval($progress['level']); ?></span>
                <?php if ($group) { echo pd_user_group_badge_html($user); } ?>
                <?php if (!empty($user['is_admin'])) { ?><span class="pd-role-tag pd-role-admin">管理员</span><?php } ?>
                <?php if (!empty($user['is_moderator'])) { ?><span class="pd-role-tag pd-role-mod">版主</span><?php } ?>
            </div>
            <p class="pd-profile-handle">@<?php echo h($user['username']); ?></p>
            <?php if (trim((string)$user['signature']) !== '') { ?>
                <p class="pd-profile-sign"><?php echo h($user['signature']); ?></p>
            <?php } ?>
            <div class="pd-profile-level">
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
        </div>
        <div class="pd-profile-actions">
            <?php if ($is_self) { ?>
                <a class="btn btn-light btn-small" href="<?php echo h(pd_url_page('profile.php')); ?>"><i class="fa-solid fa-gear" aria-hidden="true"></i> 编辑资料</a>
            <?php } elseif ($me) { ?>
                <a class="btn btn-small" href="<?php echo h(pd_url_messages(0, $uid)); ?>"><i class="fa-regular fa-envelope" aria-hidden="true"></i> 发私信</a>
            <?php } else { ?>
                <a class="btn btn-light btn-small" href="<?php echo h(pd_url_page('login.php')); ?>"><i class="fa-regular fa-envelope" aria-hidden="true"></i> 登录后私信</a>
            <?php } ?>
        </div>
    </section>

    <section class="pd-profile-stats" aria-label="用户统计">
        <div><b><?php echo pd_format_compact_number($stat['threads']); ?></b><span>主题</span></div>
        <div><b><?php echo pd_format_compact_number($stat['posts']); ?></b><span>回复</span></div>
        <div><b><?php echo pd_format_compact_number($stat['likes']); ?></b><span>获赞</span></div>
        <div><b><?php echo pd_format_compact_number($stat['views']); ?></b><span>主题浏览</span></div>
        <div><b><?php echo pd_format_compact_number($user_points); ?></b><span>积分</span></div>
        <div><b><?php echo pd_format_compact_number(intval($user['coins'])); ?></b><span>金币</span></div>
    </section>

    <div class="pd-profile-body">
        <div class="pd-profile-content">
            <div class="pd-profile-tabs" role="tablist">
                <button type="button" class="active" data-profile-tab="threads" role="tab">主题 <span><?php echo intval($stat['threads']); ?></span></button>
                <button type="button" data-profile-tab="posts" role="tab">回复 <span><?php echo intval($stat['posts']); ?></span></button>
            </div>

            <section class="pd-profile-panel thread-list" data-profile-panel="threads">
                <?php $count = 0; while ($threads && ($t = mysqli_fetch_assoc($threads))) { $count++; ?>
                    <?php echo pd_render_thread_row($t, array('variant' => 'list', 'meta' => 'user', 'avatar_link' => false)); ?>
                <?php } ?>
                <?php if ($count === 0) { ?><p class="pd-empty">还没有发布主题。</p><?php } ?>
            </section>

            <section class="pd-profile-panel thread-list" data-profile-panel="posts" hidden>
                <?php $count = 0; while ($posts && ($p = mysqli_fetch_assoc($posts))) { $count++; ?>
                    <div class="pd-profile-reply">
                        <a class="pd-profile-reply-title" href="<?php echo h(pd_url_thread($p['thread_id'])); ?>#replies"><?php echo h($p['title']); ?></a>
                        <?php
                        $excerpt = trim(strip_tags((string)$p['content']));
                        $excerpt = function_exists('mb_substr') ? mb_substr($excerpt, 0, 90, 'UTF-8') : substr($excerpt, 0, 180);
                        ?>
                        <p class="pd-profile-reply-body"><?php echo h($excerpt); ?></p>
                        <p class="pd-profile-reply-meta"><?php echo pd_time_html($p['created_at']); ?></p>
                    </div>
                <?php } ?>
                <?php if ($count === 0) { ?><p class="pd-empty">还没有回复。</p><?php } ?>
            </section>
        </div>

        <aside class="pd-profile-side">
            <section class="pd-side-card">
                <h2><span></span>资料</h2>
                <dl class="pd-profile-meta">
                    <dt>用户组</dt><dd><?php echo $group ? h($group['name']) : '普通会员'; ?></dd>
                    <dt>等级</dt><dd>Lv.<?php echo intval($progress['level']); ?> · <?php echo h(pd_level_name($progress['level'])); ?></dd>
                    <?php if (!empty($user['gender']) && $user['gender'] !== '保密') { ?>
                        <dt>性别</dt><dd><?php echo h($user['gender']); ?></dd>
                    <?php } ?>
                    <dt>注册于</dt><dd><?php echo pd_time_html($user['created_at']); ?></dd>
                    <dt>最后活跃</dt><dd><?php echo $last_seen !== '' ? pd_time_html($last_seen) : '—'; ?></dd>
                    <dt>精华主题</dt><dd><?php echo intval($stat['good']); ?></dd>
                    <dt>累计签到</dt><dd><?php echo intval($stat['signins']); ?> 天</dd>
                </dl>
            </section>
        </aside>
    </div>
</div>
<script>
(function () {
    var tabs = document.querySelectorAll('[data-profile-tab]');
    if (!tabs.length) return;
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-profile-tab');
            tabs.forEach(function (b) { b.classList.toggle('active', b === btn); });
            document.querySelectorAll('[data-profile-panel]').forEach(function (p) {
                p.hidden = p.getAttribute('data-profile-panel') !== key;
            });
        });
    });
})();
</script>
<?php pd_include_footer(); ?>
