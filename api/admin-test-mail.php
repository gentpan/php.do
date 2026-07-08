<?php
require_once __DIR__ . '/../functions.php';
$me = current_user();
if (!$me || empty($me['is_admin'])) {
    http_response_code(403);
    exit('需要管理员权限');
}
$result = '';
$result_ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim((string)(isset($_POST['to']) ? $_POST['to'] : ''));
    $err = '';
    $subject = pd_site_name() . ' 邮件测试';
    $html = '<p>这是一封来自 ' . h(pd_site_name()) . ' 的测试邮件。</p><p>发送方式：<b>' . h(pd_mail_method()) . '</b>，时间：' . date('Y-m-d H:i:s') . '</p>';
    if (pd_send_mail($to, $subject, $html, $err)) {
        $result_ok = true;
        $result = '已发送到 ' . $to . '，请查收（含垃圾箱）。';
    } else {
        $result = '发送失败：' . $err;
    }
}
$default_to = trim((string)(isset($me['email']) ? $me['email'] : '')) ?: pd_contact_email();
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>发送测试邮件</title><link rel="stylesheet" href="assets/main.css"></head>
<body><main class="wrap narrow">
<section class="card" style="max-width:520px;margin:32px auto;padding:24px 26px">
    <h1 style="margin-top:0">发送测试邮件</h1>
    <p class="muted">当前发送方式：<b><?php echo h(pd_mail_method()); ?></b><?php echo pd_mail_enabled() ? '' : '（未启用，请先到后台「邮件」配置）'; ?></p>
    <?php if ($result !== '') { ?><div class="alert <?php echo $result_ok ? 'success' : ''; ?>"><?php echo h($result); ?></div><?php } ?>
    <form method="post">
        <label>收件邮箱</label>
        <input name="to" type="email" required value="<?php echo h($default_to); ?>" style="width:100%;height:42px;margin:6px 0 14px;padding:0 12px;border:1px solid #d8d8d8;border-radius:6px">
        <input type="hidden" name="csrf_token" value="<?php echo h(pd_csrf_token()); ?>">
        <button class="btn" type="submit">发送测试</button>
        <a class="btn btn-light" href="<?php echo h(pd_url_page('index.php')); ?>">返回</a>
    </form>
</section>
</main></body></html>
