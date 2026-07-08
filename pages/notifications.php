<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
$uid = intval($u['id']);
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ignore_all']) && pd_notifications_ready()) {
        mysqli_query(db(), "UPDATE pd_notifications SET is_read=1 WHERE user_id={$uid}");
        $notice = '已忽略全部提示消息。';
    }
    if (isset($_POST['notification_sound_enabled'])) {
        $sound_enabled = $_POST['notification_sound_enabled'] === '1' ? 1 : 0;
        mysqli_query(db(), "UPDATE pd_users SET notification_sound_enabled={$sound_enabled} WHERE id={$uid}");
        $u['notification_sound_enabled'] = $sound_enabled;
        $notice = $sound_enabled ? '已开启语音提示。' : '已关闭语音提示。';
    }
}
$items = pd_notifications_ready() ? mysqli_query(db(), "SELECT * FROM pd_notifications WHERE user_id={$uid} ORDER BY id DESC LIMIT 50") : false;
if (pd_notifications_ready()) {
    mysqli_query(db(), "UPDATE pd_notifications SET is_read=1 WHERE user_id={$uid}");
}
$page_title = '消息提醒 - ' . SITE_NAME;
pd_include_header();
?>
<section class="card">
    <h1>消息提醒</h1>
    <?php if ($notice !== '') { ?><div class="alert success"><?php echo h($notice); ?></div><?php } ?>
    <div class="notification-actions">
        <form method="post">
            <button class="btn btn-small btn-light" type="submit" name="ignore_all" value="1">忽略全部提示消息</button>
        </form>
        <form method="post">
            <?php if (pd_notification_sound_enabled($u)) { ?>
                <input type="hidden" name="notification_sound_enabled" value="0">
                <button class="btn btn-small btn-light" type="submit">关闭语音提示</button>
            <?php } else { ?>
                <input type="hidden" name="notification_sound_enabled" value="1">
                <button class="btn btn-small btn-light" type="submit">开启语音提示</button>
            <?php } ?>
        </form>
    </div>
    <?php if ($items && mysqli_num_rows($items) > 0) { ?>
        <div class="notification-list">
            <?php while ($n = mysqli_fetch_assoc($items)) { ?>
                <a class="notification-item" href="<?php echo h(pd_url_page('thread.php', array('id' => intval($n['thread_id'])), 'replies')); ?>">
                    <strong><?php echo h($n['message']); ?></strong>
                    <span><?php echo pd_time_html($n['created_at']); ?></span>
                </a>
            <?php } ?>
        </div>
    <?php } else { ?>
        <p class="muted">暂无消息。</p>
    <?php } ?>
</section>
<?php pd_include_footer(); ?>
