<?php
/* core/security.php — 由 functions.php 自动切分。集中 20 个定义。 */

// IP 徽章 HTML（带 data-ip-geo，供前端异步查询地理位置与国旗）
function pd_ip_badge_html($ip) {
    $ip = trim((string)$ip);
    if ($ip === '') {
        return '';
    }
    return '<span class="action-badge action-badge-static pd-ip-badge" data-ip-geo="' . h($ip) . '" title="IP: ' . h($ip) . '"><i class="fa-solid fa-network-wired pd-ip-icon" aria-hidden="true"></i><span class="pd-ip-flag-wrap" hidden></span><span class="pd-ip-detail">IP: ' . h($ip) . '</span></span>';
}

function pd_captcha_enabled() {
    return intval(pd_setting('captcha_enabled', '1')) === 1;
}

function pd_captcha_free_count() {
    return pd_setting_int('captcha_reply_free_count', 10, 0, 10000);
}

function pd_captcha_required($scene, $user = null) {
    if (!pd_captcha_enabled()) {
        return false;
    }
    if ($scene === 'register') {
        return true;
    }
    $free_count = pd_captcha_free_count();
    if ($user && intval($user['reply_count']) >= $free_count) {
        return false;
    }
    return true;
}

function pd_webauthn_rp_id() {
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';
    $host = preg_replace('/:\d+$/', '', $host);
    return $host !== '' ? $host : 'localhost';
}

function pd_webauthn_origin() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return ($https ? 'https://' : 'http://') . pd_webauthn_rp_id();
}

class QfCborReader {
    private string $data;
    private int $pos = 0;

    public function __construct(string $data) {
        $this->data = $data;
    }

    public function read() {
        if ($this->pos >= strlen($this->data)) {
            throw new Exception('CBOR 数据不完整。');
        }
        $initial = ord($this->data[$this->pos++]);
        $major = $initial >> 5;
        $ai = $initial & 31;
        $value = $this->readLength($ai);
        if ($major === 0) return $value;
        if ($major === 1) return -1 - $value;
        if ($major === 2) return $this->readBytes($value);
        if ($major === 3) return $this->readBytes($value);
        if ($major === 4) {
            $arr = array();
            for ($i = 0; $i < $value; $i++) $arr[] = $this->read();
            return $arr;
        }
        if ($major === 5) {
            $map = array();
            for ($i = 0; $i < $value; $i++) {
                $key = $this->read();
                $map[$key] = $this->read();
            }
            return $map;
        }
        if ($major === 7) {
            if ($ai === 20) return false;
            if ($ai === 21) return true;
            if ($ai === 22) return null;
        }
        throw new Exception('暂不支持的 CBOR 格式。');
    }

    private function readLength(int $ai): int {
        if ($ai < 24) return $ai;
        if ($ai === 24) return ord($this->readBytes(1));
        if ($ai === 25) {
            $v = unpack('n', $this->readBytes(2));
            return intval($v[1]);
        }
        if ($ai === 26) {
            $v = unpack('N', $this->readBytes(4));
            return intval($v[1]);
        }
        if ($ai === 27) {
            $v = unpack('J', $this->readBytes(8));
            return intval($v[1]);
        }
        throw new Exception('暂不支持不定长 CBOR。');
    }

    private function readBytes(int $length): string {
        if ($length < 0 || $this->pos + $length > strlen($this->data)) {
            throw new Exception('CBOR 数据长度错误。');
        }
        $out = substr($this->data, $this->pos, $length);
        $this->pos += $length;
        return $out;
    }
}

function pd_der_len($len) {
    if ($len < 128) return chr($len);
    $bytes = '';
    while ($len > 0) {
        $bytes = chr($len & 0xff) . $bytes;
        $len >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function pd_der_seq($body) {
    return "\x30" . pd_der_len(strlen($body)) . $body;
}

function pd_der_bit_string($body) {
    return "\x03" . pd_der_len(strlen($body) + 1) . "\x00" . $body;
}

function pd_der_oid($oid) {
    $parts = array_map('intval', explode('.', $oid));
    $body = chr($parts[0] * 40 + $parts[1]);
    for ($i = 2; $i < count($parts); $i++) {
        $n = $parts[$i];
        $chunk = chr($n & 0x7f);
        while ($n >>= 7) {
            $chunk = chr(0x80 | ($n & 0x7f)) . $chunk;
        }
        $body .= $chunk;
    }
    return "\x06" . pd_der_len(strlen($body)) . $body;
}

function pd_webauthn_ec2_pem($cose) {
    if (!isset($cose[-2], $cose[-3]) || strlen($cose[-2]) !== 32 || strlen($cose[-3]) !== 32) {
        return '';
    }
    $algorithm = pd_der_seq(pd_der_oid('1.2.840.10045.2.1') . pd_der_oid('1.2.840.10045.3.1.7'));
    $point = "\x04" . $cose[-2] . $cose[-3];
    $spki = pd_der_seq($algorithm . pd_der_bit_string($point));
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function pd_webauthn_rsa_pem($cose) {
    if (!isset($cose[-1], $cose[-2])) {
        return '';
    }
    $n = $cose[-1];
    $e = $cose[-2];
    if ($n === '' || $e === '') return '';
    if ((ord($n[0]) & 0x80) !== 0) $n = "\x00" . $n;
    if ((ord($e[0]) & 0x80) !== 0) $e = "\x00" . $e;
    $rsa_public_key = pd_der_seq("\x02" . pd_der_len(strlen($n)) . $n . "\x02" . pd_der_len(strlen($e)) . $e);
    $algorithm = pd_der_seq(pd_der_oid('1.2.840.113549.1.1.1') . "\x05\x00");
    $spki = pd_der_seq($algorithm . pd_der_bit_string($rsa_public_key));
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function pd_webauthn_public_key_pem($cose_raw) {
    $cose = pd_cbor_decode($cose_raw);
    if (!is_array($cose) || !isset($cose[1])) return '';
    if (intval($cose[1]) === 2) return pd_webauthn_ec2_pem($cose);
    if (intval($cose[1]) === 3) return pd_webauthn_rsa_pem($cose);
    return '';
}

function pd_webauthn_verify_client($client_data_json, $expected_type, $expected_challenge) {
    $client = json_decode($client_data_json, true);
    if (!is_array($client) || !isset($client['type'], $client['challenge'], $client['origin'])) return false;
    if ($client['type'] !== $expected_type) return false;
    if (!hash_equals($expected_challenge, (string)$client['challenge'])) return false;
    return hash_equals(pd_webauthn_origin(), (string)$client['origin']);
}

function pd_webauthn_auth_data_info($auth_data) {
    if (strlen($auth_data) < 37) {
        throw new Exception('认证器数据不完整。');
    }
    if (!hash_equals(hash('sha256', pd_webauthn_rp_id(), true), substr($auth_data, 0, 32))) {
        throw new Exception('Passkey 域名不匹配。');
    }
    $counter = unpack('N', substr($auth_data, 33, 4));
    return array('flags' => ord($auth_data[32]), 'sign_count' => intval($counter[1]));
}

function pd_passkey_count($user_id) {
    pd_ensure_account_auth_schema();
    return count_rows("SELECT COUNT(*) FROM pd_passkeys WHERE user_id=" . intval($user_id));
}

function pd_ip_is_private_or_local($ip) {
    $ip = trim((string)$ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

function pd_security_logs_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_security_logs'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_security_guard() {
    if (PHP_SAPI === 'cli') {
        return;
    }
    $ip = client_ip();
    if ($ip === '' || intval(pd_setting('cc_enabled', '0')) !== 1) {
        return;
    }
    if (!pd_security_logs_ready()) {
        return;
    }
    if (ip_banned($ip)) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('当前 IP 已被封禁，请稍后再访问。');
    }
    $window = intval(pd_setting('cc_window_seconds', '60'));
    $limit = intval(pd_setting('cc_limit_count', '60'));
    $ban_hours = intval(pd_setting('cc_ban_hours', '2'));
    if ($window < 10) $window = 60;
    if ($limit < 5) $limit = 60;
    if ($ban_hours < 1) $ban_hours = 2;
    $ip_sql = esc($ip);
    $uri = isset($_SERVER['REQUEST_URI']) ? clean_text($_SERVER['REQUEST_URI'], 255) : '';
    $uri_sql = esc($uri);
    mysqli_query(db(), "INSERT INTO pd_security_logs (ip, uri, created_at) VALUES ('{$ip_sql}', '{$uri_sql}', NOW())");
    mysqli_query(db(), "DELETE FROM pd_security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $count = count_rows("SELECT COUNT(*) FROM pd_security_logs WHERE ip='{$ip_sql}' AND created_at >= DATE_SUB(NOW(), INTERVAL {$window} SECOND)");
    if ($count > $limit) {
        $reason = esc('防CC自动封禁：' . $window . '秒内访问' . $count . '次');
        mysqli_query(db(), "INSERT INTO pd_bans (ip, reason, expires_at, created_at) VALUES ('{$ip_sql}', '{$reason}', DATE_ADD(NOW(), INTERVAL {$ban_hours} HOUR), NOW())");
        header('Content-Type: text/html; charset=utf-8', true, 429);
        exit('访问过于频繁，当前 IP 已被临时封禁。');
    }
}
