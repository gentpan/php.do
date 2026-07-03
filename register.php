<?php
require_once __DIR__ . '/functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $nickname = clean_text(isset($_POST['nickname']) ? $_POST['nickname'] : '', 30);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    if (qf_captcha_required('register') && !qf_verify_captcha()) {
        $error = '验证码错误，请重新输入。';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = '用户名只能使用字母、数字、下划线，长度 3-30。';
    } elseif ($nickname === '' || strlen($password) < 6) {
        $error = '昵称不能为空，密码至少 6 位。';
    } else {
        $daily_limit = intval(qf_setting('register_ip_daily_limit', '5'));
        if ($daily_limit < 1) {
            $daily_limit = 5;
        }
        $ip_raw = client_ip();
        $ip_check = esc($ip_raw);
        $today_count = count_rows("SELECT COUNT(*) FROM qf_users WHERE ip='{$ip_check}' AND created_at >= CURDATE()");
        if ($today_count >= $daily_limit) {
            $error = '当前 IP 今天注册次数已达到上限。';
        } else {
        $u = esc($username);
        $n = esc($nickname);
        $p = qf_password_hash($password);
        $ip = esc($ip_raw);
        if (mysqli_query(db(), "INSERT INTO qf_users (username,password,nickname,ip,created_at) VALUES ('{$u}','{$p}','{$n}','{$ip}',NOW())")) {
            session_regenerate_id(true);
            $_SESSION['qf_uid'] = mysqli_insert_id(db());
            redirect(qf_url_page('index.php'));
        } else {
            $error = '注册失败，用户名可能已存在。';
        }
        }
    }
    $_SESSION['auth_modal'] = 'register';
    $_SESSION['auth_error'] = $error;
    $_SESSION['auth_register_username'] = $username;
    $_SESSION['auth_register_nickname'] = $nickname;
    redirect(qf_url_page('index.php'));
}
redirect(qf_url_page('index.php', array('auth' => 'register')));
