<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_register_username = isset($_SESSION['auth_register_username']) ? (string)$_SESSION['auth_register_username'] : '';
$auth_register_nickname = isset($_SESSION['auth_register_nickname']) ? (string)$_SESSION['auth_register_nickname'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_register_username'], $_SESSION['auth_register_nickname']);
$page_title = '注册 - ' . SITE_NAME;
$reg_has_side = true; // 始终显示第三方注册/登录（GitHub / Google）
pd_include_header(true);
?>
<div class="pd-info">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>注册</strong>
    </div>

    <section class="pd-info-block pd-auth2<?php echo $reg_has_side ? '' : ' pd-auth2--single'; ?>">
        <div class="pd-auth2-main">
            <h1>创建账号</h1>
            <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
            <form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'register'))); ?>">
                <div class="pd-auth-field">
                    <label>用户名</label>
                    <input name="username" value="<?php echo h($auth_register_username); ?>" required autocomplete="username" placeholder="用于登录的用户名">
                </div>
                <div class="pd-auth-field">
                    <label>昵称</label>
                    <input name="nickname" value="<?php echo h($auth_register_nickname); ?>" required autocomplete="nickname" placeholder="展示给他人的昵称">
                </div>
                <div class="pd-auth-field">
                    <label>密码</label>
                    <input type="password" name="password" required autocomplete="new-password" placeholder="设置登录密码">
                </div>
                <?php if (pd_require_invite()) { ?>
                <div class="pd-auth-field">
                    <label>邀请码</label>
                    <input name="invite_code" maxlength="32" required autocomplete="off" placeholder="请输入邀请码">
                </div>
                <?php } ?>
                <?php if (pd_captcha_required('register')) { ?>
                <div class="pd-auth-field"><?php echo pd_render_captcha(); ?></div>
                <?php } ?>
                <button class="pd-auth-submit" type="submit">注册</button>
            </form>
        </div>

        <?php if ($reg_has_side) { ?>
        <div class="pd-auth2-side">
            <p class="pd-auth-side-label">使用第三方账号注册 / 登录</p>
            <div class="pd-auth-oauth">
                <?php foreach (pd_oauth_providers() as $key => $info) { ?>
                    <a href="<?php echo h(pd_url_page('api/oauth.php', array('provider' => $key, 'action' => 'start'))); ?>">
                        <i class="<?php echo h($info['icon']); ?>" aria-hidden="true"></i> 使用 <?php echo h($info['label']); ?> 继续
                    </a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </section>

    <p class="pd-auth2-switch">已有账号？<a href="<?php echo h(pd_url_page('login.php')); ?>">登录</a></p>
</div>
<?php pd_include_footer(); ?>
