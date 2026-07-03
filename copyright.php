<?php
require_once __DIR__ . '/config.php';
session_start();
$message = '请勿修改版权信息，本站正在维护升级中~';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>网站维护中</title>
    <style>
        body {
            margin: 0;
            background: #fff;
            color: #243241;
            font-family: "Microsoft YaHei", "PingFang SC", Arial, sans-serif;
        }
        .box {
            width: 560px;
            max-width: 92%;
            margin: 12vh auto;
            padding: 34px;
            border: 1px solid #e5edf3;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(42, 80, 110, .08);
            text-align: center;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 24px;
            color: #12344d;
        }
        p {
            color: #6f7f8f;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>程序版权信息被修改或隐藏，系统已自动进入维护状态。</p>
    </div>
</body>
</html>
