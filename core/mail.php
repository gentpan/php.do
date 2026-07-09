<?php
/* core/mail.php — 可配置邮件系统：Resend(HTTP API) / SMTP / PHP mail()。
   后台「邮件」设置：mail_method, mail_from_email, mail_from_name,
   smtp_host/port/secure/username/password, resend_api_key。 */

function pd_mail_method() {
    $m = strtolower(trim((string) pd_setting('mail_method', 'none')));
    return in_array($m, array('mail', 'smtp', 'resend'), true) ? $m : 'none';
}

function pd_mail_enabled() {
    return pd_mail_method() !== 'none';
}

function pd_mail_from() {
    $email = trim((string) pd_setting('mail_from_email', ''));
    if ($email === '') {
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:.*/', '', $_SERVER['HTTP_HOST']) : 'localhost';
        $email = 'no-reply@' . $host;
    }
    $name = trim((string) pd_setting('mail_from_name', ''));
    if ($name === '') $name = pd_site_name();
    return array('email' => $email, 'name' => $name);
}

function pd_mail_encode_header($text) {
    $text = str_replace(array("\r", "\n"), '', (string) $text);
    if (preg_match('/[^\x20-\x7e]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
    return $text;
}

function pd_mail_from_header($from) {
    return pd_mail_encode_header($from['name']) . ' <' . $from['email'] . '>';
}

// 是否强制邮箱验证码（需邮件系统已启用 + 后台开关）
function pd_require_email_verify() {
    return pd_mail_enabled() && intval(pd_setting('require_email_verify', '0')) === 1;
}

// 发送邮箱验证码（会话存储，60s 限流，10 分钟有效）。$purpose: register|reset
function pd_email_code_send($email, $purpose, &$error = '') {
    $email = strtolower(trim((string) $email));
    $purpose = preg_replace('/[^a-z]/', '', (string) $purpose);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = '邮箱格式无效'; return false; }
    if (!pd_mail_enabled()) { $error = '邮件系统未启用，无法发送验证码'; return false; }
    $key = 'email_code_' . $purpose;
    if (isset($_SESSION[$key]['sent_at']) && (time() - intval($_SESSION[$key]['sent_at'])) < 60) {
        $error = '发送过于频繁，请 60 秒后再试。'; return false;
    }
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $site = pd_site_name();
    $subject = $site . ' 验证码：' . $code;
    $html = '<div style="font-family:sans-serif;font-size:15px;color:#333">'
        . '<p>你正在 ' . h($site) . ' 进行' . ($purpose === 'reset' ? '找回密码' : '注册') . '，验证码：</p>'
        . '<p style="font-size:26px;font-weight:800;letter-spacing:3px;color:#505b93">' . $code . '</p>'
        . '<p style="color:#888">验证码 10 分钟内有效。如非本人操作，请忽略本邮件。</p></div>';
    if (!pd_send_mail($email, $subject, $html, $error)) return false;
    $_SESSION[$key] = array('email' => $email, 'code' => $code, 'expires' => time() + 600, 'sent_at' => time());
    return true;
}

function pd_email_code_verify($email, $code, $purpose) {
    $email = strtolower(trim((string) $email));
    $code = trim((string) $code);
    $key = 'email_code_' . preg_replace('/[^a-z]/', '', (string) $purpose);
    if (empty($_SESSION[$key])) return false;
    $s = $_SESSION[$key];
    if (strtolower((string) $s['email']) !== $email) return false;
    if (time() > intval($s['expires'])) return false;
    return $code !== '' && hash_equals((string) $s['code'], $code);
}

function pd_email_code_clear($purpose) {
    unset($_SESSION['email_code_' . preg_replace('/[^a-z]/', '', (string) $purpose)]);
}

// 注册成功后的欢迎邮件（尽力发送，失败不影响注册流程）。
function pd_send_welcome_mail($email, $username) {
    $email = strtolower(trim((string) $email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (!pd_mail_enabled()) return false;
    $site = pd_site_name();
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-:]/', '', $_SERVER['HTTP_HOST']) : '';
    $home = $host !== '' ? $scheme . '://' . $host . '/' : '/';
    $subject = '欢迎加入 ' . $site;
    $html = '<div style="font-family:sans-serif;font-size:15px;color:#333;line-height:1.7">'
        . '<p>你好 <strong>' . h($username) . '</strong>，</p>'
        . '<p>欢迎加入 <strong>' . h($site) . '</strong>！你的账号已注册成功。</p>'
        . '<p>来这里，拓一方净土，重现互联网精神。</p>'
        . '<p style="margin-top:22px"><a href="' . h($home) . '" style="display:inline-block;padding:10px 22px;background:#505b93;color:#fff;text-decoration:none;border-radius:8px;font-weight:600">开始逛论坛</a></p>'
        . '<p style="color:#888;margin-top:22px">如果这不是你本人的操作，请忽略本邮件。</p></div>';
    $err = '';
    return pd_send_mail($email, $subject, $html, $err);
}

// 统一发信入口。成功返回 true；失败返回 false 并写 $error。
function pd_send_mail($to, $subject, $html, &$error = '') {
    $error = '';
    $to = trim((string) $to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $error = '收件邮箱无效'; return false; }
    $method = pd_mail_method();
    if ($method === 'none') { $error = '邮件系统未启用'; return false; }
    if ($method === 'resend') return pd_mail_via_resend($to, $subject, $html, $error);
    if ($method === 'smtp')   return pd_mail_via_smtp($to, $subject, $html, $error);
    return pd_mail_via_php($to, $subject, $html, $error);
}

function pd_mail_via_php($to, $subject, $html, &$error) {
    $from = pd_mail_from();
    $headers = 'From: ' . pd_mail_from_header($from) . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n";
    $ok = @mail($to, pd_mail_encode_header($subject), $html, $headers, '-f' . $from['email']);
    if (!$ok) { $error = 'PHP mail() 发送失败（服务器可能未配置 MTA）'; return false; }
    return true;
}

function pd_mail_via_resend($to, $subject, $html, &$error) {
    $key = trim((string) pd_setting('resend_api_key', ''));
    if ($key === '') { $error = 'Resend API Key 未配置'; return false; }
    if (!function_exists('curl_init')) { $error = '服务器无 curl'; return false; }
    $from = pd_mail_from();
    $payload = json_encode(array(
        'from' => $from['name'] . ' <' . $from['email'] . '>',
        'to' => array($to),
        'subject' => (string) $subject,
        'html' => (string) $html,
    ));
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $key, 'Content-Type: application/json'),
    ));
    $resp = curl_exec($ch);
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $cerr = curl_error($ch);
    curl_close($ch);
    if ($code >= 200 && $code < 300) return true;
    $error = 'Resend 发送失败（HTTP ' . $code . '）' . ($cerr ? '：' . $cerr : (': ' . substr((string) $resp, 0, 200)));
    return false;
}

function pd_mail_via_smtp($to, $subject, $html, &$error) {
    $host = trim((string) pd_setting('smtp_host', ''));
    $port = intval(pd_setting('smtp_port', 587));
    $secure = strtolower(trim((string) pd_setting('smtp_secure', 'tls'))); // tls|ssl|none
    $user = trim((string) pd_setting('smtp_username', ''));
    $pass = (string) pd_setting('smtp_password', '');
    if ($host === '' || $port <= 0) { $error = 'SMTP 主机/端口未配置'; return false; }
    $from = pd_mail_from();
    $ehlo_host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$fp) { $error = 'SMTP 连接失败：' . $errstr; return false; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function ($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };
    $expect = function ($resp, $prefix) { return strpos(ltrim($resp), $prefix) === 0; };

    $read(); // 220 greeting
    $cmd('EHLO ' . $ehlo_host);
    if ($secure === 'tls') {
        $r = $cmd('STARTTLS');
        if (!$expect($r, '220')) { $error = 'STARTTLS 拒绝：' . trim($r); fclose($fp); return false; }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (!@stream_socket_enable_crypto($fp, true, $crypto)) { $error = 'TLS 握手失败'; fclose($fp); return false; }
        $cmd('EHLO ' . $ehlo_host);
    }
    if ($user !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (!$expect($r, '235')) { $error = 'SMTP 认证失败：' . trim($r); fclose($fp); return false; }
    }
    $r = $cmd('MAIL FROM:<' . $from['email'] . '>');
    if (!$expect($r, '250')) { $error = 'MAIL FROM 被拒：' . trim($r); fclose($fp); return false; }
    $r = $cmd('RCPT TO:<' . $to . '>');
    if (!$expect($r, '250') && !$expect($r, '251')) { $error = 'RCPT 被拒：' . trim($r); fclose($fp); return false; }
    $r = $cmd('DATA');
    if (!$expect($r, '354')) { $error = 'DATA 被拒：' . trim($r); fclose($fp); return false; }

    $headers = 'From: ' . pd_mail_from_header($from) . "\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: ' . pd_mail_encode_header($subject) . "\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n";
    $body = preg_replace('/^\./m', '..', str_replace("\r\n", "\n", (string) $html));
    $body = str_replace("\n", "\r\n", $body);
    $r = $cmd($headers . "\r\n" . $body . "\r\n.");
    if (!$expect($r, '250')) { $error = '邮件被拒：' . trim($r); fclose($fp); return false; }
    $cmd('QUIT');
    fclose($fp);
    return true;
}
