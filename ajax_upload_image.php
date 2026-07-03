<?php
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

$u = require_login();
if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
    echo json_encode(array('ok' => 0, 'error' => '没有选择图片。'));
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array('ok' => 0, 'error' => '图片上传失败，错误码：' . intval($file['error'])));
    exit;
}

$max_mb = qf_upload_max_mb();
if ($file['size'] > $max_mb * 1024 * 1024) {
    echo json_encode(array('ok' => 0, 'error' => '图片上传失败，文件超过 ' . $max_mb . 'MB。'));
    exit;
}

$original = $file['name'];
$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
$image_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
if (!in_array($ext, $image_exts) || !in_array($ext, qf_upload_allowed_exts())) {
    echo json_encode(array('ok' => 0, 'error' => '图片上传失败，格式不支持。'));
    exit;
}
if (@getimagesize($file['tmp_name']) === false) {
    echo json_encode(array('ok' => 0, 'error' => '图片上传失败，文件内容不是有效图片。'));
    exit;
}

$safe_name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
if (qf_s3_enabled() || qf_qiniu_enabled()) {
    $remote_error = '';
    $content_type = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : 'application/octet-stream';
    if (!$content_type || strpos($content_type, 'image/') !== 0) {
        $content_type = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
    }
    $url = qf_remote_upload_file($file['tmp_name'], $safe_name, $content_type, $remote_error);
    if ($url === '') {
        echo json_encode(array('ok' => 0, 'error' => '图片上传失败，' . $remote_error));
        exit;
    }
} else {
    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        echo json_encode(array('ok' => 0, 'error' => '图片上传失败，uploads 目录创建失败。'));
        exit;
    }
    if (!is_writable($upload_dir)) {
        echo json_encode(array('ok' => 0, 'error' => '图片上传失败，uploads 目录不可写。'));
        exit;
    }
    qf_ensure_upload_protection();
    $target = $upload_dir . '/' . $safe_name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(array('ok' => 0, 'error' => '图片上传失败，保存失败。'));
        exit;
    }
    $url = 'uploads/' . $safe_name;
}

echo json_encode(array('ok' => 1, 'url' => $url, 'message' => '图片上传成功'));
exit;
?>
