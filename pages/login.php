<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_login_username = isset($_SESSION['auth_login_username']) ? (string)$_SESSION['auth_login_username'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_login_username']);
$page_title = '登录 - ' . SITE_NAME;
pd_include_header();
?>
<section class="card pd-auth-page">
    <div class="pd-auth-head">
        <h1>登录</h1>
        <p>进入你的 php.do 账号，继续发帖、回复和管理资料。</p>
    </div>
    <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
    <form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'login'))); ?>">
        <label>用户名</label>
        <input name="username" value="<?php echo h($auth_login_username); ?>" required autocomplete="username">
        <label>密码</label>
        <input type="password" name="password" required autocomplete="current-password">
        <button class="btn auth-submit" type="submit">登录</button>
        <button class="btn btn-light auth-passkey" type="button" data-passkey-login><i class="fa-solid fa-key" aria-hidden="true"></i> 使用 Passkey 登录</button>
    </form>
    <?php if (pd_oauth_any_enabled()) { ?>
    <div class="pd-oauth-divider"><span>或使用第三方账号</span></div>
    <div class="pd-oauth-buttons">
        <?php foreach (pd_oauth_providers() as $key => $info) { if (!pd_oauth_enabled($key)) continue; ?>
            <a class="btn btn-light pd-oauth-btn pd-oauth-<?php echo $key; ?>" href="<?php echo h(pd_url_page('api/oauth.php', array('provider' => $key, 'action' => 'start'))); ?>">
                <i class="<?php echo h($info['icon']); ?>" aria-hidden="true"></i> 使用 <?php echo h($info['label']); ?> 登录
            </a>
        <?php } ?>
    </div>
    <?php } ?>
    <p class="pd-auth-switch">还没有账号？<a href="<?php echo h(pd_url_page('register.php')); ?>">注册</a></p>
</section>
<?php pd_include_footer(); ?>
