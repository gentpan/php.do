<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$reset_email = isset($_SESSION['auth_reset_email']) ? (string)$_SESSION['auth_reset_email'] : '';
unset($_SESSION['auth_error'], $_SESSION['auth_reset_email']);
$page_title = '找回密码 - ' . SITE_NAME;
$fp_send_code_url = pd_url_page('api/send-email-code.php');
pd_include_header(true);
?>
<div class="pd-info">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>找回密码</strong>
    </div>

    <section class="pd-info-block pd-auth2 pd-auth2--single">
        <div class="pd-auth2-main">
            <h1>找回密码</h1>
            <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
            <?php if (!pd_mail_enabled()) { ?><div class="alert">邮件系统尚未启用，暂时无法通过邮箱找回密码，请联系管理员。</div><?php } ?>
            <form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'reset'))); ?>" data-reset-form>
                <div class="pd-auth-field">
                    <label>绑定邮箱</label>
                    <input type="email" name="email" value="<?php echo h($reset_email); ?>" required autocomplete="email" placeholder="注册时绑定的邮箱">
                </div>
                <div class="pd-auth-field">
                    <label>邮箱验证码</label>
                    <div class="pd-input-affix">
                        <input name="email_code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required placeholder="6 位验证码" data-reg-code>
                        <button type="button" class="pd-affix-btn pd-affix-text" data-send-code>发送验证码</button>
                    </div>
                </div>
                <div class="pd-auth-field">
                    <label>新密码</label>
                    <div class="pd-input-affix">
                        <input type="password" name="password" required autocomplete="new-password" placeholder="至少 8 位，不能纯数字" data-reg-pw>
                        <button type="button" class="pd-affix-btn" data-gen-password title="生成随机密码"><i class="fa-solid fa-dice"></i></button>
                    </div>
                </div>
                <div class="pd-auth-field">
                    <label>确认新密码</label>
                    <input type="password" name="password_confirm" required autocomplete="new-password" placeholder="再次输入新密码" data-reg-pw2>
                </div>
                <button class="pd-auth-submit" type="submit">重置密码</button>
            </form>
        </div>
    </section>

    <p class="pd-auth2-switch">想起来了？<a href="<?php echo h(pd_url_page('login.php')); ?>">返回登录</a></p>
</div>
<script>
(function () {
    function rand(s) { return s[Math.floor(Math.random() * s.length)]; }
    var pBtn = document.querySelector('[data-gen-password]');
    if (pBtn) pBtn.addEventListener('click', function () {
        var lo = 'abcdefghijkmnpqrstuvwxyz', up = 'ABCDEFGHJKLMNPQRSTUVWXYZ', di = '23456789', sy = '!@#$%-_';
        var all = lo + up + di + sy, n = 10 + Math.floor(Math.random() * 7), arr = [rand(lo), rand(up), rand(di), rand(sy)];
        for (var i = arr.length; i < n; i++) arr.push(rand(all));
        for (var j = arr.length - 1; j > 0; j--) { var k = Math.floor(Math.random() * (j + 1)), t = arr[j]; arr[j] = arr[k]; arr[k] = t; }
        var pw = arr.join(''), f1 = document.querySelector('[data-reg-pw]'), f2 = document.querySelector('[data-reg-pw2]');
        if (f1) { f1.type = 'text'; f1.value = pw; } if (f2) { f2.type = 'text'; f2.value = pw; }
    });
    var form = document.querySelector('[data-reset-form]');
    if (form) form.addEventListener('submit', function (e) {
        var p1 = document.querySelector('[data-reg-pw]'), p2 = document.querySelector('[data-reg-pw2]');
        if (p1 && p2 && p1.value !== p2.value) { e.preventDefault(); (window.pdToast || window.alert)('两次输入的密码不一致'); }
    });
    var cBtn = document.querySelector('[data-send-code]');
    if (cBtn) cBtn.addEventListener('click', function () {
        var el = document.querySelector('input[name="email"]'), email = el ? el.value.trim() : '';
        if (!email) { (window.pdToast || window.alert)('请先填写邮箱'); return; }
        cBtn.disabled = true;
        fetch(<?php echo json_encode($fp_send_code_url); ?>, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.pdCsrfToken || '' },
            body: 'purpose=reset&email=' + encodeURIComponent(email)
        }).then(function (r) { return r.json(); }).then(function (d) {
            (window.pdToast || window.alert)(d.ok ? d.message : d.error);
            if (d.ok) { var left = 60; cBtn.textContent = left + 's'; var t = setInterval(function () { left--; if (left <= 0) { clearInterval(t); cBtn.disabled = false; cBtn.textContent = '发送验证码'; } else cBtn.textContent = left + 's'; }, 1000); }
            else { cBtn.disabled = false; }
        }).catch(function () { cBtn.disabled = false; (window.pdToast || window.alert)('网络错误，请重试'); });
    });
})();
</script>
<?php pd_include_footer(); ?>
