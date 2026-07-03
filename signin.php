<?php
require_once __DIR__ . '/functions.php';
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $ok = qf_signin_reward(intval($u['id']), $message);
    if ($ok) {
        $_SESSION['signin_modal'] = '恭喜你签到成功！' . $message;
    } else {
        $_SESSION['flash'] = $message;
    }
}
$back = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '' ? $_SERVER['HTTP_REFERER'] : qf_url_page('index.php');
header('Location: ' . $back);
exit;
?>
