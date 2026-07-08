<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_login_username = isset($_SESSION['auth_login_username']) ? (string)$_SESSION['auth_login_username'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_login_username']);
$page_title = '登录 - ' . SITE_NAME;
pd_auth_shell_head($page_title, '登录', '进入你的 php.do 账号，继续发帖、回复和管理资料。', $auth_error);
?>
<form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'login'))); ?>" class="mt-3 flex flex-col gap-3">
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">用户名</span>
        <input name="username" value="<?php echo h($auth_login_username); ?>" required autocomplete="username" class="input input-bordered w-full">
    </label>
    <label class="w-full">
        <span class="mb-1 block text-sm font-medium">密码</span>
        <input type="password" name="password" required autocomplete="current-password" class="input input-bordered w-full">
    </label>
    <button class="btn btn-primary w-full" type="submit">登录</button>
    <button class="btn btn-outline w-full" type="button" data-passkey-login><i class="fa-solid fa-key mr-1"></i> 使用 Passkey 登录</button>
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
<p class="text-sm opacity-70 mt-3">还没有账号？<a class="link link-primary" href="<?php echo h(pd_url_page('register.php')); ?>">注册</a></p>
<?php pd_auth_shell_foot(); ?>
