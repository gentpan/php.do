<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => 0, 'error' => 'Method not allowed'));
    exit;
}
if (!qf_verify_csrf()) {
    echo json_encode(array('ok' => 0, 'error' => 'CSRF 校验失败'));
    exit;
}

$markdown = isset($_POST['markdown']) ? (string)$_POST['markdown'] : '';
$max = 50000;
if (function_exists('mb_strlen')) {
    if (mb_strlen($markdown, 'UTF-8') > $max) {
        $markdown = mb_substr($markdown, 0, $max, 'UTF-8');
    }
} elseif (strlen($markdown) > $max) {
    $markdown = substr($markdown, 0, $max);
}

$html = qf_markdown($markdown);
if (trim($html) === '') {
    $html = '<div class="empty">预览会显示在这里</div>';
}
echo json_encode(array('ok' => 1, 'html' => $html));
exit;
