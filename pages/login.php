<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_login_username = isset($_SESSION['auth_login_username']) ? (string)$_SESSION['auth_login_username'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_login_username']);
$page_title = '登录 - ' . SITE_NAME;
pd_include_header(true);
?>
<div class="pd-info">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>登录</strong>
    </div>

    <section class="pd-info-block pd-auth2">
        <div class="pd-auth2-main">
            <h1>欢迎回来</h1>
            <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
            <form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'login'))); ?>">
                <div class="pd-auth-field">
                    <label>电子邮件 / 用户名</label>
                    <input name="username" value="<?php echo h($auth_login_username); ?>" required autocomplete="username" placeholder="电子邮件或用户名">
                </div>
                <div class="pd-auth-field">
                    <label>密码</label>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="密码">
                </div>
                <div class="pd-auth-forgot"><a href="<?php echo h(pd_url_page('forgot-password.php')); ?>">忘记密码？</a></div>
                <button class="pd-auth-submit" type="submit">登录</button>
            </form>
        </div>

        <div class="pd-auth2-side">
            <p class="pd-auth-side-label">使用以下方式登录</p>
            <div class="pd-auth-oauth">
                <?php foreach (pd_oauth_providers() as $key => $info) { ?>
                    <a href="<?php echo h(pd_url_page('api/oauth.php', array('provider' => $key, 'action' => 'start'))); ?>">
                        <img class="pd-oauth-logo" src="<?php echo h($info['logo']); ?>" alt="" width="18" height="18"> 使用 <?php echo h($info['label']); ?> 登录
                    </a>
                <?php } ?>
                <button type="button" data-passkey-login>
                    <i class="fa-solid fa-key pd-oauth-ficon" aria-hidden="true"></i> 使用通行密钥登录
                </button>
            </div>
        </div>
    </section>

    <p class="pd-auth2-switch">还没有账号？<a href="<?php echo h(pd_url_page('register.php')); ?>">注册</a></p>
</div>
<?php pd_include_footer(); ?>
