<?php
/* core/geoip.php — 由 functions.php 自动切分。集中 5 个定义。 */

function pd_geoip_cache_dir() {
    $dir = PD_ROOT . '/storage/geoip';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function pd_geoip_cache_file($ip) {
    return pd_geoip_cache_dir() . '/' . hash('sha256', $ip) . '.json';
}

function pd_geoip_cache_read($ip) {
    $file = pd_geoip_cache_file($ip);
    if (!is_file($file)) {
        return null;
    }
    $cached = json_decode((string)@file_get_contents($file), true);
    if (!is_array($cached)) {
        return null;
    }
    $ttl = !empty($cached['_miss']) ? 3600 : (86400 * 7); // 失败短缓存 1h，成功 7 天
    $cached_at = isset($cached['_cached_at']) ? intval($cached['_cached_at']) : filemtime($file);
    if ((time() - $cached_at) >= $ttl) {
        return null;
    }
    unset($cached['_miss'], $cached['_cached_at']);
    return $cached;
}

function pd_geoip_cache_write($ip, $data, $is_miss = false) {
    $dir = pd_geoip_cache_dir();
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    $payload = $data;
    $payload['_cached_at'] = time();
    if ($is_miss) {
        $payload['_miss'] = 1;
    }
    $file = pd_geoip_cache_file($ip);
    $tmp = $file . '.' . getmypid() . '.tmp';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    return @rename($tmp, $file);
}

function pd_geoip_lookup($ip) {
    static $mem = array();
    $ip = trim((string)$ip);
    $empty = array('ip' => $ip, 'country' => '', 'country_code' => '', 'region' => '', 'city' => '', 'isp' => '', 'flag' => '');
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return $empty;
    }
    if (isset($mem[$ip])) {
        return $mem[$ip];
    }

    $cached = pd_geoip_cache_read($ip);
    if (is_array($cached)) {
        $mem[$ip] = $cached;
        return $mem[$ip];
    }

    // 私网/保留地址不打外部 API，直接落盘，避免无意义的远程请求
    if (pd_ip_is_private_or_local($ip)) {
        $mem[$ip] = $empty;
        pd_geoip_cache_write($ip, $empty, false);
        return $mem[$ip];
    }

    $url = 'https://api.cnip.io/geoip/' . rawurlencode($ip);
    $raw = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'php.do-geoip/1.0',
        ));
        $raw = (string)curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(array('http' => array('timeout' => 3, 'ignore_errors' => true, 'header' => "User-Agent: php.do-geoip/1.0\r\n")));
        $raw = (string)@file_get_contents($url, false, $ctx);
    }
    $json = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($json)) {
        $mem[$ip] = $empty;
        pd_geoip_cache_write($ip, $empty, true); // 负缓存 1 小时，避免反复打 API
        return $mem[$ip];
    }
    $country = trim((string)(isset($json['country']) ? $json['country'] : ''));
    if ($country === '保留') {
        $country = '';
    }
    $country_code = strtoupper(trim((string)(isset($json['country_code']) ? $json['country_code'] : '')));
    // 国旗改由前端 flag-icons 依据 country_code 渲染（<span class="fi fi-xx">），不再存储 URL。
    $flag = '';
    $mem[$ip] = array(
        'ip' => $ip,
        'country' => $country,
        'country_code' => $country_code,
        'region' => trim((string)(isset($json['region']) ? $json['region'] : '')),
        'city' => trim((string)(isset($json['city']) ? $json['city'] : '')),
        'isp' => trim((string)(isset($json['isp']) ? $json['isp'] : '')),
        'flag' => $flag,
    );
    if ($mem[$ip]['region'] === '保留') $mem[$ip]['region'] = '';
    if ($mem[$ip]['city'] === '保留') $mem[$ip]['city'] = '';
    pd_geoip_cache_write($ip, $mem[$ip], false);
    return $mem[$ip];
}
