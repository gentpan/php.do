<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(qf_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_login_username = isset($_SESSION['auth_login_username']) ? (string)$_SESSION['auth_login_username'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_login_username']);
$page_title = '登录 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card phpdo-auth-page">
    <div class="phpdo-auth-head">
        <h1>登录</h1>
        <p>进入你的 php.do 账号，继续发帖、回复和管理资料。</p>
    </div>
    <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
    <form method="post" action="<?php echo h(qf_url_page('api/auth.php', array('action' => 'login'))); ?>">
        <label>用户名</label>
        <input name="username" value="<?php echo h($auth_login_username); ?>" required autocomplete="username">
        <label>密码</label>
        <input type="password" name="password" required autocomplete="current-password">
        <button class="btn auth-submit" type="submit">登录</button>
        <button class="btn btn-light auth-passkey" type="button" data-passkey-login><i class="fa-solid fa-key" aria-hidden="true"></i> 使用 Passkey 登录</button>
    </form>
    <p class="phpdo-auth-switch">还没有账号？<a href="<?php echo h(qf_url_page('register.php')); ?>">注册</a></p>
</section>
<?php qf_include_footer(); ?>
