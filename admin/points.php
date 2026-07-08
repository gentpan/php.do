<?php
require_once __DIR__ . '/../functions.php';
require_admin();
qf_ensure_points_schema();

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $points_thread = max(0, intval($_POST['points_thread']));
    $points_reply = max(0, intval($_POST['points_reply']));
    $points_floor_reply = max(0, intval($_POST['points_floor_reply']));
    $points_good_bonus = max(0, intval($_POST['points_good_bonus']));
    $level_thresholds = trim((string)$_POST['level_thresholds']);
    $level_names = trim((string)$_POST['level_names']);

    // 简单校验阈值
    $parsed = array();
    foreach (preg_split('/\r\n|\r|\n/', $level_thresholds) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ':') === false) continue;
        list($lv, $need) = array_map('trim', explode(':', $line, 2));
        $lv = intval($lv);
        $need = intval($need);
        if ($lv >= 1 && $lv <= 50) $parsed[$lv] = max(0, $need);
    }
    if (empty($parsed)) {
        $error = '等级阈值至少需要 1 行有效配置，例如 1:0';
    } else {
        ksort($parsed, SORT_NUMERIC);
        $lines = array();
        foreach ($parsed as $lv => $need) {
            $lines[] = $lv . ':' . $need;
        }
        qf_update_setting('points_thread', strval($points_thread));
        qf_update_setting('points_reply', strval($points_reply));
        qf_update_setting('points_floor_reply', strval($points_floor_reply));
        qf_update_setting('points_good_bonus', strval($points_good_bonus));
        qf_update_setting('level_thresholds', implode("\n", $lines));
        qf_update_setting('level_names', $level_names);
        $saved = true;
    }
}

$logs = mysqli_query(db(), "SELECT l.*, u.nickname, u.username FROM qf_points_log l LEFT JOIN qf_users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 40");
$page_title = '积分与等级 - ' . SITE_NAME;
qf_include_admin_header();
?>
<section class="card">
    <div class="admin-page-title"><h1>积分与等级</h1></div>
<?php if ($saved) { ?><div class="alert success">设置已保存。</div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>

    <form method="post" class="settings-form">
        <h2>奖励分值</h2>
        <div class="admin-points-grid">
            <div>
                <label>发布主题</label>
                <input type="number" name="points_thread" min="0" max="100000" value="<?php echo intval(qf_points_for_thread()); ?>">
            </div>
            <div>
                <label>发表回复</label>
                <input type="number" name="points_reply" min="0" max="100000" value="<?php echo intval(qf_points_for_reply()); ?>">
            </div>
            <div>
                <label>楼中楼回复</label>
                <input type="number" name="points_floor_reply" min="0" max="100000" value="<?php echo intval(qf_points_for_floor_reply()); ?>">
            </div>
            <div>
                <label>加精奖励</label>
                <input type="number" name="points_good_bonus" min="0" max="100000" value="<?php echo intval(qf_points_for_good()); ?>">
            </div>
        </div>
        <p class="muted">删除主题/回复会扣回对应分值；取消加精也会扣回加精奖励。</p>

        <h2>等级阈值</h2>
        <textarea name="level_thresholds" rows="12" placeholder="1:0&#10;2:30"><?php echo h(qf_setting('level_thresholds', '')); ?></textarea>
        <p class="muted">每行一条：等级:所需累计积分。例如 <code>3:100</code> 表示 Lv.3 需要至少 100 积分。</p>

        <h2>等级名称</h2>
        <textarea name="level_names" rows="12" placeholder="1:新手"><?php echo h(qf_setting('level_names', '')); ?></textarea>
        <p class="muted">每行一条：等级:名称。例如 <code>1:新手</code>。可不填，前端仍显示 Lv.N。</p>

        <button class="btn" type="submit">保存积分等级设置</button>
    </form>
</section>

<section class="card">
    <h2>最近积分流水</h2>
    <table class="forum-table">
        <tr><th>时间</th><th>用户</th><th>变动</th><th>余额</th><th>原因</th><th>备注</th></tr>
        <?php $n = 0; while ($logs && $row = mysqli_fetch_assoc($logs)) { $n++; ?>
            <tr>
                <td><?php echo qf_time_html($row['created_at']); ?></td>
                <td><a href="<?php echo h(qf_url_user($row['user_id'])); ?>"><?php echo h(qf_user_display_name($row)); ?></a></td>
                <td><?php echo intval($row['delta']) > 0 ? '+' : ''; ?><?php echo intval($row['delta']); ?></td>
                <td><?php echo intval($row['balance']); ?></td>
                <td><?php echo h(qf_points_reason_label($row['reason'])); ?></td>
                <td class="muted"><?php echo h($row['note']); ?></td>
            </tr>
        <?php } ?>
        <?php if ($n === 0) { ?><tr><td colspan="6" class="muted">暂无流水。</td></tr><?php } ?>
    </table>
</section>
<?php qf_include_admin_footer(); ?>
