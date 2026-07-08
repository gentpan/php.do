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

$path = $att['file_path'];
$name = $att['original_name'] !== '' ? $att['original_name'] : basename($path);
$ext = strtolower($att['file_ext']);
$compressed_exts = array('zip', 'rar');
$dl_cost = pd_download_points_cost();
$dl_uid = $download_user ? intval($download_user['id']) : 0;
$dl_owner = intval($att['user_id']);
$dl_is_admin = $download_user && !empty($download_user['is_admin']);

// 付费附件：游客必须登录
if ($dl_cost > 0 && !$download_user) {
    pd_download_notice('需要登录', '<div class="alert">下载该附件需要登录并消耗积分。</div><p><a class="btn" href="' . h(pd_url_page('login.php')) . '">去登录</a> <a class="btn btn-light" href="' . h(pd_url_page('register.php')) . '">注册</a></p>');
}

// 压缩包：关闭游客下载时要求登录
if (!$download_user && !pd_guest_download_allowed() && in_array($ext, $compressed_exts)) {
    pd_download_notice('需要登录', '<div class="alert">需要登录才能进行此操作</div><p><a class="btn" href="' . h(pd_url_page('register.php')) . '">去注册</a> <a class="btn btn-light" href="' . h(pd_url_page('login.php')) . '">已有账号，去登录</a></p>');
}

// 下载扣积分：首次下载扣分；上传者本人与管理员免费；扣的分转给上传者
if ($dl_cost > 0 && $dl_uid > 0 && $dl_uid !== $dl_owner && !$dl_is_admin && !pd_attachment_purchased($id, $dl_uid)) {
    $br = mysqli_query(db(), "SELECT points FROM pd_users WHERE id={$dl_uid} LIMIT 1");
    $brow = $br ? mysqli_fetch_assoc($br) : null;
    $dl_balance = $brow ? intval($brow['points']) : 0;
    if ($dl_balance < $dl_cost) {
        pd_download_notice('积分不足', '<div class="alert">下载该附件需要 ' . $dl_cost . ' 积分，你当前 ' . $dl_balance . ' 积分，暂时不足。</div><p><a class="btn btn-light" href="' . h(pd_url_page('index.php')) . '">返回首页</a></p>');
    }
    pd_ensure_attachment_download_schema();
    // 唯一键 (attachment_id,user_id) + INSERT IGNORE 防并发重复扣费
    mysqli_query(db(), "INSERT IGNORE INTO pd_attachment_downloads (attachment_id,user_id,cost,created_at) VALUES ({$id},{$dl_uid},{$dl_cost},NOW())");
    if (mysqli_affected_rows(db()) > 0) {
        pd_add_user_points($dl_uid, -$dl_cost, '下载附件', 'attachment', $id);
        if ($dl_owner > 0) {
            pd_add_user_points($dl_owner, $dl_cost, '附件被下载', 'attachment', $id);
        }
    }
}

mysqli_query(db(), "UPDATE pd_attachments SET download_count=download_count+1 WHERE id={$id}");
if (preg_match('/^https?:\/\//i', $path)) {
    header('Location: ' . $path);
    exit;
}

$base_dir = realpath(PD_ROOT . '/uploads');
$file = realpath(PD_ROOT . '/' . ltrim($path, '/'));
if (!$base_dir || !$file || strpos($file, $base_dir . DIRECTORY_SEPARATOR) !== 0) {
    header('Content-Type: text/html; charset=utf-8', true, 403);
    exit('附件路径不安全');
}
if (!is_file($file)) {
    exit('附件文件不存在');
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
