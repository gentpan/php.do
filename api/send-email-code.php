<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$purpose = isset($_POST['purpose']) ? preg_replace('/[^a-z]/', '', (string) $_POST['purpose']) : 'register';
if (!in_array($purpose, array('register', 'reset', 'profile'), true)) {
    $purpose = 'register';
}
$email = strtolower($email);
$retry_after = 0;
if (!pd_rate_limit_allow('email-code-ip', client_ip(), 20, 3600, $retry_after)
    || !pd_rate_limit_allow('email-code-address', $email, 5, 3600, $retry_after)) {
    header('Retry-After: ' . intval($retry_after));
    pd_json_response(array('ok' => 0, 'error' => '验证码发送过于频繁，请稍后再试。'), 429);
}
if ($purpose === 'profile') {
    $u = require_login();
    $email_sql = esc($email);
    $exists = filter_var($email, FILTER_VALIDATE_EMAIL)
        ? mysqli_query(db(), "SELECT id FROM pd_users WHERE email='{$email_sql}' AND id<>" . intval($u['id']) . " LIMIT 1")
        : false;
    if ($exists && mysqli_num_rows($exists) > 0) {
        pd_json_response(array('ok' => 0, 'error' => '这个邮箱已经被其他账号绑定。'), 409);
    }
}
$err = '';
if (pd_email_code_send($email, $purpose, $err)) {
    echo json_encode(array('ok' => 1, 'message' => '验证码已发送，请查收邮箱（含垃圾箱）。'));
} else {
    echo json_encode(array('ok' => 0, 'error' => $err !== '' ? $err : '发送失败'));
}
exit;
