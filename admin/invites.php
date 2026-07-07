<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$saved = false;
$error = '';
$generated = array();
$me = current_user();

if (!qf_invite_table_ready()) {
    $error = '邀请码表不存在，请先访问 install/upgrade.php 升级数据库。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'settings') {
        qf_update_setting('require_invite', !empty($_POST['require_invite']) ? '1' : '0');
        $saved = true;
    } elseif ($action === 'generate') {
        $count = intval(isset($_POST['count']) ? $_POST['count'] : 1);
        if ($count < 1) { $count = 1; }
        if ($count > 100) { $count = 100; }
        $expire_days = intval(isset($_POST['expire_days']) ? $_POST['expire_days'] : 0);
        $note = clean_text(isset($_POST['note']) ? $_POST['note'] : '', 100);
        $note_sql = esc($note);
        $creator = $me ? intval($me['id']) : 0;
        $expires_sql = $expire_days > 0 ? "DATE_ADD(NOW(), INTERVAL {$expire_days} DAY)" : 'NULL';
        for ($i = 0; $i < $count; $i++) {
            $code = qf_generate_invite_code();
            $code_sql = esc($code);
            if (mysqli_query(db(), "INSERT INTO qf_invites (code,created_by,note,expires_at,created_at) VALUES ('{$code_sql}',{$creator},'{$note_sql}',{$expires_sql},NOW())")) {
                $generated[] = $code;
            }
        }
        $saved = true;
    } elseif ($action === 'delete') {
        if (!empty($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
            foreach ($_POST['delete_ids'] as $del) {
                $del = intval($del);
                if ($del > 0) {
                    mysqli_query(db(), "DELETE FROM qf_invites WHERE id={$del}");
                }
            }
        }
        $saved = true;
    }
}

$require_invite = intval(qf_setting('require_invite', '0')) === 1;
$invites = qf_invite_table_ready() ? mysqli_query(db(), "SELECT i.*, u.nickname AS used_nickname, u.username AS used_username FROM qf_invites i LEFT JOIN qf_users u ON u.id=i.used_by ORDER BY i.id DESC LIMIT 300") : false;
$page_title = '邀请码 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <div class="admin-page-title">
        <h1>邀请码</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a></p>
    <?php if ($saved) { ?><div class="alert success">操作已完成。</div><?php } ?>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>

    <?php if (!empty($generated)) { ?>
        <div class="alert success">
            本次生成 <?php echo count($generated); ?> 个邀请码：<br>
            <code style="display:inline-block;line-height:1.9;"><?php echo h(implode('   ', $generated)); ?></code>
        </div>
    <?php } ?>

    <h2>注册开关</h2>
    <form method="post">
        <input type="hidden" name="action" value="settings">
        <label><input class="inline-check" type="checkbox" name="require_invite" value="1" <?php if ($require_invite) echo 'checked'; ?>> 注册需要邀请码</label>
        <p class="muted">开启后，游客注册时必须填写有效邀请码；关闭则正常注册。Passkey / GitHub / Google 登录不受邀请码限制。</p>
        <button class="btn" type="submit">保存开关</button>
    </form>

    <h2>生成邀请码</h2>
    <form method="post">
        <input type="hidden" name="action" value="generate">
        <div class="add-forum-grid">
            <div>
                <label>数量（1-100）</label>
                <input class="forum-order-input" type="number" name="count" value="5" min="1" max="100">
            </div>
            <div>
                <label>有效天数（0 = 永久）</label>
                <input class="forum-order-input" type="number" name="expire_days" value="0" min="0" max="3650">
            </div>
            <div>
                <label>备注（可选）</label>
                <input type="text" name="note" maxlength="100" placeholder="例如：微信群第一批">
            </div>
        </div>
        <p class="muted">每个邀请码一次性有效，被成功注册使用后即失效。</p>
        <button class="btn" type="submit">生成邀请码</button>
    </form>

    <h2>邀请码列表</h2>
    <form method="post">
        <input type="hidden" name="action" value="delete">
        <table class="forum-table forum-admin-table">
            <tr><th>邀请码</th><th>状态</th><th>使用者</th><th>备注</th><th>过期时间</th><th>创建时间</th><th>删除</th></tr>
            <?php $count = 0; while ($invites && ($inv = mysqli_fetch_assoc($invites))) { $count++;
                $used = intval($inv['used_by']) > 0;
                $expired = !$used && $inv['expires_at'] !== null && strtotime($inv['expires_at']) < time();
                $status = $used ? '已使用' : ($expired ? '已过期' : '未使用');
            ?>
                <tr>
                    <td><code><?php echo h($inv['code']); ?></code></td>
                    <td><?php echo h($status); ?></td>
                    <td><?php echo $used ? h($inv['used_nickname'] !== null && $inv['used_nickname'] !== '' ? $inv['used_nickname'] : ($inv['used_username'] !== null ? $inv['used_username'] : ('#' . intval($inv['used_by'])))) : '—'; ?></td>
                    <td><?php echo h($inv['note']); ?></td>
                    <td><?php echo $inv['expires_at'] !== null ? h($inv['expires_at']) : '永久'; ?></td>
                    <td><?php echo h($inv['created_at']); ?></td>
                    <td><label><input class="inline-check" type="checkbox" name="delete_ids[]" value="<?php echo intval($inv['id']); ?>"></label></td>
                </tr>
            <?php } ?>
            <?php if ($count === 0) { ?><tr><td colspan="7">暂无邀请码。</td></tr><?php } ?>
        </table>
        <?php if ($count > 0) { ?><button class="btn btn-light" type="submit" data-confirm="确定删除勾选的邀请码？">删除勾选</button><?php } ?>
    </form>
</section>
<?php qf_include_footer(); ?>
