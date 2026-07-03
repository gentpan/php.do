<?php
require_once __DIR__ . '/db.php';
require_admin();

$keyword = clean_text(isset($_GET['q']) ? $_GET['q'] : '', 30);
$where = "is_admin=0";
if ($keyword !== '') {
    $keyword_sql = esc($keyword);
    $where .= " AND username LIKE '%{$keyword_sql}%'";
}
$users = mysqli_query(db(), "SELECT * FROM qf_users WHERE {$where} ORDER BY id DESC LIMIT 200");
$forums = mysqli_query(db(), "SELECT id,name FROM qf_forums ORDER BY display_order ASC, id ASC");
$forum_options = array();
while ($forums && $forum = mysqli_fetch_assoc($forums)) {
    $forum_options[] = $forum;
}
$page_title = '用户管理 - ' . SITE_NAME;
include __DIR__ . '/header.php';
?>
<section class="card">
    <div class="admin-page-title">
        <h1>用户管理</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin.php')); ?>">返回后台</a></p>
    <?php if (!empty($_SESSION['flash'])) { ?>
        <div class="alert success"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
    <?php } ?>
    <form class="search user-search" method="get">
        <input type="text" name="q" value="<?php echo h($keyword); ?>" placeholder="搜索用户名">
        <button class="btn" type="submit">搜索</button>
    </form>
    <p class="muted">仅显示普通用户。可按用户名搜索，并对用户执行禁止发言、修改密码、清除全部发帖和回帖。</p>
    <?php if ($users && mysqli_num_rows($users) > 0) { ?>
        <table class="forum-table user-table">
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>昵称</th>
                <th>状态</th>
                <th>权限</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
            <?php while ($user = mysqli_fetch_assoc($users)) { ?>
                <?php
                $mute_text = '正常';
                if (!empty($user['mute_until']) && strtotime($user['mute_until']) > time()) {
                    $mute_text = '禁言至 ' . $user['mute_until'];
                }
                $moderator_forum_ids = qf_moderator_forum_ids(intval($user['id']));
                ?>
                <tr>
                    <td><?php echo intval($user['id']); ?></td>
                    <td><?php echo h($user['username']); ?></td>
                    <td><?php echo h($user['nickname']); ?></td>
                    <td><?php echo h($mute_text); ?></td>
                    <td>
                        <?php if (intval(isset($user['is_moderator']) ? $user['is_moderator'] : 0)) { ?>
                            <span class="moderator-badge">版主</span>
                            <?php foreach ($forum_options as $forum) { if (in_array(intval($forum['id']), $moderator_forum_ids)) { ?><span class="muted"> · <?php echo h($forum['name']); ?></span><?php } } ?>
                        <?php } else { ?>
                            普通用户
                        <?php } ?>
                    </td>
                    <td><?php echo h($user['created_at']); ?></td>
                    <td class="user-actions">
                        <form class="user-action-moderator" method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'set_moderator'))); ?>">
                            <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                            <label>任命板块</label>
                            <select name="forum_id">
                                <option value="0">取消版主</option>
                                <?php foreach ($forum_options as $forum) { ?>
                                    <option value="<?php echo intval($forum['id']); ?>" <?php if (in_array(intval($forum['id']), $moderator_forum_ids)) echo 'selected'; ?>><?php echo h($forum['name']); ?></option>
                                <?php } ?>
                            </select>
                            <label>每日删除上限</label>
                            <input type="number" name="moderator_delete_limit" min="0" max="10000" value="<?php echo intval(isset($user['moderator_delete_limit']) ? $user['moderator_delete_limit'] : qf_moderator_daily_delete_limit()); ?>">
                            <button class="btn btn-small btn-light" type="submit">保存版主设置</button>
                        </form>
                        <form class="user-action-mute" method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'mute_user'))); ?>">
                            <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                            <input type="number" name="days" min="1" max="3650" value="7" title="禁止发言天数">
                            <button class="btn btn-small" type="submit">禁止发言</button>
                        </form>
                        <form class="user-action-password" method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'change_user_password'))); ?>">
                            <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                            <input type="password" name="password" minlength="6" placeholder="新密码" required>
                            <button class="btn btn-small" type="submit">修改密码</button>
                        </form>
                        <form class="user-action-clear" method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'clear_user_content'))); ?>" data-confirm="确定清除该用户全部发帖和回帖？此操作会把内容标记为删除。">
                            <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                            <button class="btn btn-small btn-danger" type="submit">清除发帖和回帖</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="muted">没有找到普通用户。</p>
    <?php } ?>
</section>
<?php include __DIR__ . '/footer.php'; ?>
