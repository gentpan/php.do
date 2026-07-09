<?php
require_once __DIR__ . '/../functions.php';
if (current_user()) {
    redirect(pd_url_page('index.php'));
}
$auth_error = isset($_SESSION['auth_error']) ? (string)$_SESSION['auth_error'] : '';
$auth_register_username = isset($_SESSION['auth_register_username']) ? (string)$_SESSION['auth_register_username'] : '';
$auth_register_email = isset($_SESSION['auth_register_email']) ? (string)$_SESSION['auth_register_email'] : '';
unset($_SESSION['auth_modal'], $_SESSION['auth_error'], $_SESSION['auth_register_username'], $_SESSION['auth_register_nickname'], $_SESSION['auth_register_email']);
$page_title = '注册 - ' . SITE_NAME;
$reg_email_verify = pd_require_email_verify();
$reg_send_code_url = pd_url_page('api/send-email-code.php');
pd_include_header(true);
?>
<div class="pd-info">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>注册</strong>
    </div>

    <section class="pd-info-block pd-auth2">
        <div class="pd-auth2-main">
            <h1>创建账号</h1>
            <?php if ($auth_error !== '') { ?><div class="alert auth-alert"><?php echo h($auth_error); ?></div><?php } ?>
            <form method="post" action="<?php echo h(pd_url_page('api/auth.php', array('action' => 'register'))); ?>" data-register-form>
                <div class="pd-auth-field">
                    <label>电子邮箱</label>
                    <input type="email" name="email" value="<?php echo h($auth_register_email); ?>" required autocomplete="email" placeholder="name@example.com">
                </div>
                <div class="pd-auth-field">
                    <label>用户名</label>
                    <input name="username" value="<?php echo h($auth_register_username); ?>" required autocomplete="username" placeholder="5-16 位，中英文/数字/下划线">
                    <p class="pd-field-hint">支持中英文、数字、下划线、连字符；5-16 位；不能纯数字或含句号逗号等标点。</p>
                </div>
                <div class="pd-auth-field">
                    <label>密码</label>
                    <div class="pd-input-affix">
                        <input type="password" name="password" required autocomplete="new-password" placeholder="至少 8 位，不能纯数字" data-reg-pw>
                        <button type="button" class="pd-affix-btn" data-gen-password title="生成随机密码"><i class="fa-solid fa-dice"></i></button>
                    </div>
                    <div class="pd-pw-strength" data-pw-strength hidden aria-live="polite">
                        <div class="pd-pw-strength-track"><span class="pd-pw-strength-fill"></span></div>
                        <span class="pd-pw-strength-text"></span>
                    </div>
                    <p class="pd-field-hint">点骰子可生成 10-16 位随机强密码（会同时填入确认框）。</p>
                </div>
                <div class="pd-auth-field">
                    <label>确认密码</label>
                    <input type="password" name="password_confirm" required autocomplete="new-password" placeholder="再次输入密码" data-reg-pw2>
                </div>
                <?php if ($reg_email_verify) { ?>
                <div class="pd-auth-field">
                    <label>邮箱验证码</label>
                    <div class="pd-input-affix">
                        <input name="email_code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6 位验证码" data-reg-code>
                        <button type="button" class="pd-affix-btn pd-affix-text" data-send-code>发送验证码</button>
                    </div>
                </div>
                <?php } ?>
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

        <div class="pd-auth2-side">
            <p class="pd-auth-side-label">使用第三方账号注册 / 登录</p>
            <div class="pd-auth-oauth">
                <?php foreach (pd_oauth_providers() as $key => $info) { ?>
                    <a href="<?php echo h(pd_url_page('api/oauth.php', array('provider' => $key, 'action' => 'start'))); ?>">
                        <img class="pd-oauth-logo" src="<?php echo h($info['logo']); ?>" alt="" width="18" height="18"> 使用 <?php echo h($info['label']); ?> 继续
                    </a>
                <?php } ?>
            </div>
        </div>
    </section>

    <p class="pd-auth2-switch">已有账号？<a href="<?php echo h(pd_url_page('login.php')); ?>">登录</a></p>
</div>
<script>
(function () {
    function rand(str) { return str[Math.floor(Math.random() * str.length)]; }
    // 密码实时强弱指示：与后端规则对齐（<8 位或纯数字判为弱）
    var pwField = document.querySelector('[data-reg-pw]');
    var pwBox = document.querySelector('[data-pw-strength]');
    function scorePw(pw) {
        if (!pw) return -1;
        if (pw.length < 8 || /^\d+$/.test(pw)) return 1; // 不满足最低要求 → 弱
        var variety = 0;
        if (/[a-z]/.test(pw)) variety++;
        if (/[A-Z]/.test(pw)) variety++;
        if (/\d/.test(pw)) variety++;
        if (/[^A-Za-z0-9]/.test(pw)) variety++;
        var s = variety + (pw.length >= 12 ? 1 : 0);
        if (s <= 2) return 1;   // 弱
        if (s === 3) return 2;  // 中
        return 3;               // 强
    }
    function renderPw() {
        if (!pwField || !pwBox) return;
        var lvl = scorePw(pwField.value);
        if (lvl < 0) { pwBox.hidden = true; return; }
        pwBox.hidden = false;
        var names = { 1: '弱', 2: '中', 3: '强' };
        var cls = { 1: 'is-weak', 2: 'is-mid', 3: 'is-strong' };
        pwBox.className = 'pd-pw-strength ' + cls[lvl];
        var txt = pwBox.querySelector('.pd-pw-strength-text');
        if (txt) txt.textContent = '密码强度：' + names[lvl];
    }
    if (pwField) pwField.addEventListener('input', renderPw);
    // 随机密码：10-16 位，含大小写+数字+符号，非纯数字
    var pBtn = document.querySelector('[data-gen-password]');
    if (pBtn) pBtn.addEventListener('click', function () {
        var lo = 'abcdefghijkmnpqrstuvwxyz', up = 'ABCDEFGHJKLMNPQRSTUVWXYZ', di = '23456789', sy = '!@#$%-_';
        var all = lo + up + di + sy, n = 10 + Math.floor(Math.random() * 7);
        var arr = [rand(lo), rand(up), rand(di), rand(sy)];
        for (var i = arr.length; i < n; i++) arr.push(rand(all));
        for (var j = arr.length - 1; j > 0; j--) { var k = Math.floor(Math.random() * (j + 1)); var t = arr[j]; arr[j] = arr[k]; arr[k] = t; }
        var pw = arr.join('');
        var f1 = document.querySelector('[data-reg-pw]'), f2 = document.querySelector('[data-reg-pw2]');
        if (f1) { f1.type = 'text'; f1.value = pw; }
        if (f2) { f2.type = 'text'; f2.value = pw; }
        renderPw();
    });
    // 前端确认密码一致
    var form = document.querySelector('[data-register-form]');
    if (form) form.addEventListener('submit', function (e) {
        var p1 = document.querySelector('[data-reg-pw]'), p2 = document.querySelector('[data-reg-pw2]');
        if (p1 && p2 && p1.value !== p2.value) { e.preventDefault(); (window.pdToast || window.alert)('两次输入的密码不一致'); }
    });
    // 发送邮箱验证码
    var cBtn = document.querySelector('[data-send-code]');
    if (cBtn) cBtn.addEventListener('click', function () {
        var emailEl = document.querySelector('input[name="email"]'), email = emailEl ? emailEl.value.trim() : '';
        if (!email) { (window.pdToast || window.alert)('请先填写邮箱'); return; }
        cBtn.disabled = true;
        fetch(<?php echo json_encode($reg_send_code_url); ?>, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.pdCsrfToken || '' },
            body: 'purpose=register&email=' + encodeURIComponent(email)
        }).then(function (r) { return r.json(); }).then(function (d) {
            (window.pdToast || window.alert)(d.ok ? d.message : d.error);
            if (d.ok) {
                var left = 60; cBtn.textContent = left + 's';
                var t = setInterval(function () { left--; if (left <= 0) { clearInterval(t); cBtn.disabled = false; cBtn.textContent = '发送验证码'; } else cBtn.textContent = left + 's'; }, 1000);
            } else { cBtn.disabled = false; }
        }).catch(function () { cBtn.disabled = false; (window.pdToast || window.alert)('网络错误，请重试'); });
    });
})();
</script>
<?php pd_include_footer(); ?>
