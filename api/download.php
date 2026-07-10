<?php
require_once __DIR__ . '/../functions.php';
$download_user = current_user();

function pd_safe_download_name($name) {
    $name = basename(str_replace(array("\r", "\n", '"'), '', (string)$name));
    return $name !== '' ? $name : 'download';
}

function pd_download_notice($title, $body_html) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <title><?php echo h($title); ?></title>
        <link rel="stylesheet" href="assets/main.css">
    </head>
    <body>
    <main class="wrap narrow">
        <section class="card"><?php echo $body_html; ?></section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    $id = pd_path_id();
}
$rs = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE id={$id} LIMIT 1");
$att = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$att) {
    exit('附件不存在');
}
$att = pd_migrate_attachment_to_protected_storage($att);

$path = $att['file_path'];
$name = $att['original_name'] !== '' ? $att['original_name'] : basename($path);
$ext = strtolower($att['file_ext']);
$compressed_exts = array('zip', 'rar');
$dl_cost = pd_download_points_cost();
$dl_uid = $download_user ? intval($download_user['id']) : 0;
$dl_owner = intval($att['user_id']);
$dl_is_admin = $download_user && !empty($download_user['is_admin']);

// 未绑定的预上传附件只允许上传者本人或管理员读取；已绑定附件必须属于未删除内容。
if (intval($att['thread_id']) === 0 && intval($att['post_id']) === 0) {
    if (!$download_user || (!$dl_is_admin && $dl_uid !== $dl_owner)) {
        http_response_code(404);
        exit('附件不存在');
    }
} elseif (intval($att['post_id']) > 0) {
    $parent = mysqli_query(db(), "SELECT p.id FROM pd_posts p INNER JOIN pd_threads t ON t.id=p.thread_id WHERE p.id=" . intval($att['post_id']) . " AND p.is_deleted=0 AND t.is_deleted=0 LIMIT 1");
    if (!$parent || mysqli_num_rows($parent) === 0) {
        http_response_code(404);
        exit('附件所属内容不存在');
    }
} elseif (intval($att['thread_id']) > 0) {
    $parent = mysqli_query(db(), "SELECT id FROM pd_threads WHERE id=" . intval($att['thread_id']) . " AND is_deleted=0 LIMIT 1");
    if (!$parent || mysqli_num_rows($parent) === 0) {
        http_response_code(404);
        exit('附件所属内容不存在');
    }
}

$file = false;
$remote_url = '';
$private_key = pd_s3_private_key($path);
if ($private_key !== '') {
    $remote_url = pd_s3_presigned_download_url($private_key, 120);
    if ($remote_url === '') {
        http_response_code(503);
        exit('附件存储暂不可用');
    }
} elseif (preg_match('/^https?:\/\//i', $path)) {
    $remote_url = $path; // 兼容旧版公开对象；新上传附件使用 s3-private:// 引用。
} else {
    $file = pd_resolve_local_attachment_file($path);
    if (!$file) {
        http_response_code(404);
        exit('附件文件不存在');
    }
}

// 付费附件：游客必须登录
if ($dl_cost > 0 && !$download_user) {
    pd_download_notice('需要登录', '<div class="alert">下载该附件需要登录并消耗积分。</div><p><a class="btn" href="' . h(pd_url_page('login.php')) . '">去登录</a> <a class="btn btn-light" href="' . h(pd_url_page('register.php')) . '">注册</a></p>');
}

// 压缩包：关闭游客下载时要求登录
if (!$download_user && !pd_guest_download_allowed() && in_array($ext, $compressed_exts)) {
    pd_download_notice('需要登录', '<div class="alert">需要登录才能进行此操作</div><p><a class="btn" href="' . h(pd_url_page('register.php')) . '">去注册</a> <a class="btn btn-light" href="' . h(pd_url_page('login.php')) . '">已有账号，去登录</a></p>');
}

// 下载扣积分：首次下载扣分；上传者本人与管理员免费；扣的分转给上传者
if ($dl_cost > 0 && $dl_uid > 0 && $dl_uid !== $dl_owner && !$dl_is_admin) {
    $purchase = pd_purchase_attachment($id, $dl_uid, $dl_owner, $dl_cost);
    if (empty($purchase['ok'])) {
        if (!empty($purchase['insufficient'])) {
            $dl_balance = intval($purchase['balance']);
            pd_download_notice('积分不足', '<div class="alert">下载该附件需要 ' . $dl_cost . ' 积分，你当前 ' . $dl_balance . ' 积分，暂时不足。</div><p><a class="btn btn-light" href="' . h(pd_url_page('index.php')) . '">返回首页</a></p>');
        }
        http_response_code(503);
        exit('积分结算失败，请稍后重试');
    }
}

mysqli_query(db(), "UPDATE pd_attachments SET download_count=download_count+1 WHERE id={$id}");
if ($remote_url !== '') {
    header('Cache-Control: private, no-store');
    header('Location: ' . $remote_url);
    exit;
}
$safe_name = pd_safe_download_name($name);
header('X-Content-Type-Options: nosniff');
$image_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
if (in_array($ext, $image_exts)) {
    $types = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    );
    header('Content-Type: ' . $types[$ext]);
    header('Content-Disposition: inline; filename="' . $safe_name . '"');
} else {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $safe_name . '"');
}
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
?>
