<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_raw = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $username = esc($username_raw);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $rs = mysqli_query(db(), "SELECT * FROM qf_users WHERE username='{$username}' AND status=1 LIMIT 1");
    $u = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($u && qf_password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['qf_uid'] = intval($u['id']);
        redirect(qf_url_page('index.php'));
    } else {
        $_SESSION['auth_modal'] = 'login';
        $_SESSION['auth_error'] = '用户名或密码错误。';
        $_SESSION['auth_login_username'] = $username_raw;
        redirect(qf_url_page('index.php'));
    }
}
redirect(qf_url_page('index.php', array('auth' => 'login')));
