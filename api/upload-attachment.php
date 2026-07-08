<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$u = require_login();
if (empty($_FILES['attachment']) || !is_array($_FILES['attachment'])) {
    echo json_encode(array('ok' => 0, 'error' => '没有选择附件。'));
    exit;
}

$file = $_FILES['attachment'];
$description = clean_text(isset($_POST['attachment_description']) ? $_POST['attachment_description'] : '', 120);
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array('ok' => 0, 'error' => '附件上传失败，错误码：' . intval($file['error'])));
    exit;
}

$max_mb = qf_upload_max_mb();
if ($file['size'] > $max_mb * 1024 * 1024) {
    echo json_encode(array('ok' => 0, 'error' => '附件上传失败，文件超过 ' . $max_mb . 'MB。'));
    exit;
}

$original = $file['name'];
$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
$attachment_exts = array('zip', 'rar');
if (!in_array($ext, $attachment_exts) || !in_array($ext, qf_upload_allowed_exts())) {
    echo json_encode(array('ok' => 0, 'error' => '附件上传失败，只支持 zip、rar。'));
    exit;
}

if (qf_s3_enabled()) {
    $safe_name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $remote_error = '';
    $file_path = qf_remote_upload_file($file['tmp_name'], $safe_name, 'application/octet-stream', $remote_error);
    if ($file_path === '') {
        echo json_encode(array('ok' => 0, 'error' => '附件上传失败，' . $remote_error));
        exit;
    }
} else {
    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        echo json_encode(array('ok' => 0, 'error' => '附件上传失败，uploads 目录创建失败。'));
        exit;
    }
    if (!is_writable($upload_dir)) {
        echo json_encode(array('ok' => 0, 'error' => '附件上传失败，uploads 目录不可写。'));
        exit;
    }
    qf_ensure_upload_protection();
    if (!qf_store_uploaded_attachment_file($file['tmp_name'], $ext, $file_path)) {
        echo json_encode(array('ok' => 0, 'error' => '附件上传失败，保存失败。'));
        exit;
    }
}

$path_sql = esc($file_path);
$original_sql = esc($original);
$ext_sql = esc($ext);
$size = intval($file['size']);
$user_id = intval($u['id']);
$ok = mysqli_query(db(), "INSERT INTO qf_attachments (thread_id,post_id,user_id,file_path,original_name,file_ext,file_size,created_at) VALUES (0,0,{$user_id},'{$path_sql}','{$original_sql}','{$ext_sql}',{$size},NOW())");
if (!$ok) {
    echo json_encode(array('ok' => 0, 'error' => '附件上传失败，写入数据库失败。'));
    exit;
}
$attach_id = intval(mysqli_insert_id(db()));
$url = qf_attachment_url($attach_id); // download?id= 附件ID

$tag_name = str_replace(array('[', ']', '(', ')', "\r", "\n"), '', $original);
$tag_description = str_replace(array('[', ']', '(', ')', "\r", "\n"), '', $description);
$label = $tag_description !== '' ? $tag_description : $tag_name;
if ($label === '') {
    $label = '附件';
}
$tag = '[' . $label . '](' . $url . ')';
echo json_encode(array('ok' => 1, 'id' => $attach_id, 'url' => $url, 'name' => $original, 'description' => $description, 'tag' => $tag, 'message' => '附件上传成功'));
exit;
?>
