<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(qf_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_register_username = isset($_SESSION['auth_register_username']) ? (string)$_SESSION['auth_register_username'] : '';
$auth_register_nickname = isset($_SESSION['auth_register_nickname']) ? (string)$_SESSION['auth_register_nickname'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_register_username'], $_SESSION['auth_register_nickname']);
$page_title = '注册 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card phpdo-auth-page">
    <div class="phpdo-auth-head">
        <h1>注册</h1>
        <p>创建账号后可以发布主题、参与回复、收藏自己的 PHP 技术记录。</p>
    </div>
    <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
    <form method="post" action="<?php echo h(qf_url_page('api/auth.php', array('action' => 'register'))); ?>">
        <label>用户名</label>
        <input name="username" value="<?php echo h($auth_register_username); ?>" required autocomplete="username">
        <label>昵称</label>
        <input name="nickname" value="<?php echo h($auth_register_nickname); ?>" required autocomplete="nickname">
        <label>密码</label>
        <input type="password" name="password" required autocomplete="new-password">
        <?php if (qf_captcha_required('register')) { echo qf_render_captcha(); } ?>
        <button class="btn auth-submit" type="submit">注册</button>
    </form>
    <p class="phpdo-auth-switch">已有账号？<a href="<?php echo h(qf_url_page('login.php')); ?>">登录</a></p>
</section>
<?php qf_include_footer(); ?>
