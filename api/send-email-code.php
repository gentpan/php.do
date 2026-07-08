<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$purpose = isset($_POST['purpose']) ? preg_replace('/[^a-z]/', '', (string) $_POST['purpose']) : 'register';
if (!in_array($purpose, array('register', 'reset'), true)) {
    $purpose = 'register';
}
$err = '';
if (pd_email_code_send($email, $purpose, $err)) {
    echo json_encode(array('ok' => 1, 'message' => '验证码已发送，请查收邮箱（含垃圾箱）。'));
} else {
    echo json_encode(array('ok' => 0, 'error' => $err !== '' ? $err : '发送失败'));
}
exit;
