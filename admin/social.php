<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$saved = false;
$error = '';
if (!qf_oauth_table_ready()) {
    $error = '第三方登录表 qf_oauth 不存在，请先访问 install/upgrade.php 升级数据库。';
}

$providers = qf_oauth_providers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    foreach ($providers as $key => $info) {
        qf_update_setting('oauth_' . $key . '_enabled', !empty($_POST[$key . '_enabled']) ? '1' : '0');
        if (isset($_POST[$key . '_client_id'])) {
            qf_update_setting('oauth_' . $key . '_client_id', trim(clean_text($_POST[$key . '_client_id'], 191)));
        }
        // secret 留空表示不修改
        $secret = isset($_POST[$key . '_client_secret']) ? trim((string)$_POST[$key . '_client_secret']) : '';
        if ($secret !== '') {
            qf_update_setting('oauth_' . $key . '_client_secret', substr($secret, 0, 255));
        }
    }
    $saved = true;
}

$page_title = '社交登录 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <div class="admin-page-title">
        <h1>社交登录（GitHub / Google）</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a></p>
    <?php if ($saved) { ?><div class="alert success">社交登录设置已保存。</div><?php } ?>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>

    <div class="alert">
        <strong>配置步骤：</strong>到对应平台创建 OAuth 应用，把下面每个平台的“回调地址”原样填入平台的 Authorization callback URL，然后把拿到的 Client ID / Client Secret 填到这里并勾选启用。
        <ul style="margin:8px 0 0;padding-left:20px;line-height:1.9;">
            <li>GitHub：Settings → Developer settings → OAuth Apps → New OAuth App</li>
            <li>Google：Google Cloud Console → APIs &amp; Services → Credentials → OAuth client ID（类型选 Web application）</li>
        </ul>
    </div>

    <form method="post">
        <?php foreach ($providers as $key => $info) {
            $enabled = intval(qf_setting('oauth_' . $key . '_enabled', '0')) === 1;
            $client_id = qf_setting('oauth_' . $key . '_client_id', '');
            $has_secret = trim(qf_setting('oauth_' . $key . '_client_secret', '')) !== '';
        ?>
            <h2><i class="<?php echo h($info['icon']); ?>" aria-hidden="true"></i> <?php echo h($info['label']); ?></h2>
            <label><input class="inline-check" type="checkbox" name="<?php echo $key; ?>_enabled" value="1" <?php if ($enabled) echo 'checked'; ?>> 启用 <?php echo h($info['label']); ?> 登录</label>

            <label>回调地址（Authorization callback URL / Redirect URI）</label>
            <input type="text" value="<?php echo h(qf_oauth_redirect_uri($key)); ?>" readonly onclick="this.select()">
            <p class="muted">把这个地址原样复制到平台的回调地址设置里。</p>

            <label>Client ID</label>
            <input type="text" name="<?php echo $key; ?>_client_id" value="<?php echo h($client_id); ?>" maxlength="191" autocomplete="off">

            <label>Client Secret <?php if ($has_secret) { ?><span class="muted">（已设置，留空则不修改）</span><?php } ?></label>
            <input type="password" name="<?php echo $key; ?>_client_secret" value="" maxlength="255" autocomplete="new-password" placeholder="<?php echo $has_secret ? '••••••••（留空保持不变）' : '请输入 Client Secret'; ?>">
            <hr>
        <?php } ?>
        <button class="btn" type="submit">保存社交登录设置</button>
    </form>
</section>
<?php qf_include_footer(); ?>
