<?php
require_once __DIR__ . '/db.php';
require_admin();

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cc_enabled = !empty($_POST['cc_enabled']) ? '1' : '0';
    $cc_window_seconds = intval($_POST['cc_window_seconds']);
    $cc_limit_count = intval($_POST['cc_limit_count']);
    $cc_ban_hours = intval($_POST['cc_ban_hours']);

    if ($cc_window_seconds < 10) {
        $cc_window_seconds = 60;
    }
    if ($cc_window_seconds > 3600) {
        $cc_window_seconds = 3600;
    }
    if ($cc_limit_count < 5) {
        $cc_limit_count = 60;
    }
    if ($cc_limit_count > 10000) {
        $cc_limit_count = 10000;
    }
    if ($cc_ban_hours < 1) {
        $cc_ban_hours = 2;
    }
    if ($cc_ban_hours > 720) {
        $cc_ban_hours = 720;
    }

    qf_update_setting('cc_enabled', $cc_enabled);
    qf_update_setting('cc_window_seconds', strval($cc_window_seconds));
    qf_update_setting('cc_limit_count', strval($cc_limit_count));
    qf_update_setting('cc_ban_hours', strval($cc_ban_hours));
    $saved = true;
}

$recent_bans = mysqli_query(db(), "SELECT * FROM qf_bans ORDER BY id DESC LIMIT 20");
$page_title = '安全相关 - ' . SITE_NAME;
include __DIR__ . '/header.php';
?>
<section class="card">
    <div class="admin-page-title">
        <h1>安全相关</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin.php')); ?>">返回后台</a></p>
    <?php if ($saved) { ?><div class="alert success">安全设置已保存。</div><?php } ?>
    <form method="post">
        <label>防 CC 访问限制</label>
        <label><input class="inline-check" type="checkbox" name="cc_enabled" value="1" <?php if (intval(qf_setting('cc_enabled', '0')) === 1) echo 'checked'; ?>> 开启防 CC</label>
        <p class="muted">开启后，系统会统计同一 IP 在指定时间内的访问次数，超过阈值后自动写入封禁 IP。</p>

        <label>统计时间窗口（秒）</label>
        <input type="number" name="cc_window_seconds" min="10" max="3600" value="<?php echo h(qf_setting('cc_window_seconds', '60')); ?>">
        <p class="muted">例如 60 表示统计 1 分钟内的访问次数。</p>

        <label>允许访问次数</label>
        <input type="number" name="cc_limit_count" min="5" max="10000" value="<?php echo h(qf_setting('cc_limit_count', '60')); ?>">
        <p class="muted">例如设置 60，即同一 IP 在上面的时间窗口内访问超过 60 次会被封禁。</p>

        <label>超过限制后封禁小时数</label>
        <input type="number" name="cc_ban_hours" min="1" max="720" value="<?php echo h(qf_setting('cc_ban_hours', '2')); ?>">
        <p class="muted">例如设置 2，触发后该 IP 会被封禁 2 小时。</p>

        <button class="btn" type="submit">保存安全设置</button>
    </form>
</section>

<section class="card">
    <h2>最近封禁 IP</h2>
    <?php if ($recent_bans && mysqli_num_rows($recent_bans) > 0) { ?>
        <table class="forum-table">
            <tr><th>IP</th><th>原因</th><th>到期时间</th><th>创建时间</th></tr>
            <?php while ($ban = mysqli_fetch_assoc($recent_bans)) { ?>
                <tr>
                    <td><?php echo h($ban['ip']); ?></td>
                    <td><?php echo h($ban['reason']); ?></td>
                    <td><?php echo $ban['expires_at'] ? h($ban['expires_at']) : '永久'; ?></td>
                    <td><?php echo h($ban['created_at']); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="muted">暂无封禁记录。</p>
    <?php } ?>
</section>
<?php include __DIR__ . '/footer.php'; ?>
