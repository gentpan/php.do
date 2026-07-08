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
pd_auth_shell_head($page_title, '注册', '创建账号后可以发布主题、参与回复、收藏自己的 PHP 技术记录。', $auth_error);
?>
<form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'register'))); ?>" class="mt-3 flex flex-col gap-3">
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">用户名</span>
        <input name="username" value="<?php echo h($auth_register_username); ?>" required autocomplete="username" class="input input-bordered w-full">
    </label>
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">昵称</span>
        <input name="nickname" value="<?php echo h($auth_register_nickname); ?>" required autocomplete="nickname" class="input input-bordered w-full">
    </label>
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">密码</span>
        <input type="password" name="password" required autocomplete="new-password" class="input input-bordered w-full">
    </label>
    <?php if (pd_require_invite()) { ?>
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">邀请码</span>
        <input name="invite_code" maxlength="32" required autocomplete="off" placeholder="请输入邀请码" class="input input-bordered w-full">
    </label>
    <?php } ?>
    <?php if (pd_captcha_required('register')) { ?>
    <div class="pd-tw-captcha [&_img]:rounded [&_input]:input [&_input]:input-bordered [&_input]:w-full">
        <?php echo pd_render_captcha(); ?>
    </div>
    <?php } ?>
    <button class="btn btn-primary w-full" type="submit">注册</button>
</form>
<?php if (pd_oauth_any_enabled()) { ?>
<div class="divider text-xs opacity-50">或使用第三方账号</div>
<div class="flex flex-col gap-2">
    <?php foreach (pd_oauth_providers() as $key => $info) { if (!pd_oauth_enabled($key)) continue; ?>
        <a class="btn btn-ghost border border-base-300 w-full justify-start" href="<?php echo h(pd_url_page('api/oauth.php', array('provider' => $key, 'action' => 'start'))); ?>">
            <i class="<?php echo h($info['icon']); ?>" aria-hidden="true"></i> 使用 <?php echo h($info['label']); ?> 登录
        </a>
    <?php } ?>
</div>
<?php } ?>
<p class="text-sm opacity-70 mt-3">已有账号？<a class="link link-primary" href="<?php echo h(pd_url_page('login.php')); ?>">登录</a></p>
<?php pd_auth_shell_foot(); ?>
