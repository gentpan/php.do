<?php
require_once __DIR__ . '/db.php';
$download_user = current_user();

function qf_safe_download_name($name) {
    $name = basename(str_replace(array("\r", "\n", '"'), '', (string)$name));
    return $name !== '' ? $name : 'download';
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    $id = qf_path_id();
}
$rs = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE id={$id} LIMIT 1");
$att = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$att) {
    exit('附件不存在');
}

$path = $att['file_path'];
$name = $att['original_name'] !== '' ? $att['original_name'] : basename($path);
$ext = strtolower($att['file_ext']);
$compressed_exts = array('zip', 'rar');
if (!$download_user && !qf_guest_download_allowed() && in_array($ext, $compressed_exts)) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <title>需要登录</title>
        <link rel="stylesheet" href="assets/style.css">
        <script>
        if (confirm('需要登录才能进行此操作')) {
            window.location.href = '<?php echo h(qf_url_page('register.php')); ?>';
        }
        </script>
    </head>
    <body>
    <main class="wrap narrow">
        <section class="card">
            <div class="alert">需要登录才能进行此操作</div>
            <p><a class="btn" href="<?php echo h(qf_url_page('register.php')); ?>">去注册</a> <a class="btn btn-light" href="<?php echo h(qf_url_page('login.php')); ?>">已有账号，去登录</a></p>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

mysqli_query(db(), "UPDATE qf_attachments SET download_count=download_count+1 WHERE id={$id}");
if (preg_match('/^https?:\/\//i', $path)) {
    header('Location: ' . $path);
    exit;
}

$base_dir = realpath(__DIR__ . '/uploads');
$file = realpath(__DIR__ . '/' . ltrim($path, '/'));
if (!$base_dir || !$file || strpos($file, $base_dir . DIRECTORY_SEPARATOR) !== 0) {
    header('Content-Type: text/html; charset=utf-8', true, 403);
    exit('附件路径不安全');
}
if (!is_file($file)) {
    exit('附件文件不存在');
}
$safe_name = qf_safe_download_name($name);
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
