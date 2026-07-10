<?php
/* core/media.php — 由 functions.php 自动切分。集中 16 个定义。 */

function pd_add_user_points($user_id, $delta, $reason = '', $ref_type = '', $ref_id = 0, $note = '', $operator_id = 0) {
    $user_id = intval($user_id);
    $delta = intval($delta);
    if ($user_id <= 0 || $delta === 0) return false;
    $conn = db();
    mysqli_begin_transaction($conn);
    $ok = mysqli_query($conn, "UPDATE pd_users SET points = GREATEST(0, points + ({$delta})) WHERE id={$user_id}");
    $rs = $ok ? mysqli_query($conn, "SELECT points FROM pd_users WHERE id={$user_id} LIMIT 1 FOR UPDATE") : false;
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$row) {
        mysqli_rollback($conn);
        return false;
    }
    $balance = $row ? intval($row['points']) : 0;
    $reason_sql = esc(clean_text($reason, 40));
    $ref_type_sql = esc(clean_text($ref_type, 20));
    $ref_id = intval($ref_id);
    $note_sql = esc(clean_text($note, 255));
    $operator_id = intval($operator_id);
    $logged = mysqli_query($conn, "INSERT INTO pd_points_log (user_id,delta,balance,reason,ref_type,ref_id,note,operator_id,created_at) VALUES ({$user_id},{$delta},{$balance},'{$reason_sql}','{$ref_type_sql}',{$ref_id},'{$note_sql}',{$operator_id},NOW())");
    if (!$logged) {
        mysqli_rollback($conn);
        return false;
    }
    mysqli_commit($conn);
    pd_sync_user_group($user_id);
    return true;
}

function pd_purchase_attachment($attachment_id, $buyer_id, $owner_id, $cost) {
    $attachment_id = intval($attachment_id);
    $buyer_id = intval($buyer_id);
    $owner_id = intval($owner_id);
    $cost = max(0, intval($cost));
    $conn = db();
    mysqli_begin_transaction($conn);

    $lock_ids = array_values(array_unique(array_filter(array($buyer_id, $owner_id))));
    sort($lock_ids, SORT_NUMERIC);
    $locked = array();
    $rs = mysqli_query($conn, "SELECT id,points,status FROM pd_users WHERE id IN (" . implode(',', $lock_ids) . ") ORDER BY id FOR UPDATE");
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $locked[intval($row['id'])] = $row;
    }
    $buyer = isset($locked[$buyer_id]) && intval($locked[$buyer_id]['status']) === 1 ? $locked[$buyer_id] : null;
    if (!$buyer || intval($buyer['points']) < $cost) {
        mysqli_rollback($conn);
        return array('ok' => false, 'insufficient' => true, 'balance' => $buyer ? intval($buyer['points']) : 0);
    }

    $inserted = mysqli_query($conn, "INSERT IGNORE INTO pd_attachment_downloads (attachment_id,user_id,cost,created_at) VALUES ({$attachment_id},{$buyer_id},{$cost},NOW())");
    if (!$inserted) {
        mysqli_rollback($conn);
        return array('ok' => false, 'insufficient' => false, 'balance' => intval($buyer['points']));
    }
    if (mysqli_affected_rows($conn) === 0) {
        mysqli_commit($conn);
        return array('ok' => true, 'charged' => false, 'balance' => intval($buyer['points']));
    }

    $new_buyer_balance = intval($buyer['points']) - $cost;
    if (!mysqli_query($conn, "UPDATE pd_users SET points={$new_buyer_balance} WHERE id={$buyer_id}")) {
        mysqli_rollback($conn);
        return array('ok' => false, 'insufficient' => false, 'balance' => intval($buyer['points']));
    }
    $buyer_logged = mysqli_query($conn, "INSERT INTO pd_points_log (user_id,delta,balance,reason,ref_type,ref_id,note,operator_id,created_at) VALUES ({$buyer_id},-{$cost},{$new_buyer_balance},'download','attachment',{$attachment_id},'',0,NOW())");
    if (!$buyer_logged) {
        mysqli_rollback($conn);
        return array('ok' => false, 'insufficient' => false, 'balance' => intval($buyer['points']));
    }

    if ($owner_id > 0 && isset($locked[$owner_id])) {
        $owner = $locked[$owner_id];
        if ($owner) {
            $new_owner_balance = intval($owner['points']) + $cost;
            $owner_updated = mysqli_query($conn, "UPDATE pd_users SET points={$new_owner_balance} WHERE id={$owner_id}");
            $owner_logged = $owner_updated && mysqli_query($conn, "INSERT INTO pd_points_log (user_id,delta,balance,reason,ref_type,ref_id,note,operator_id,created_at) VALUES ({$owner_id},{$cost},{$new_owner_balance},'attachment_download','attachment',{$attachment_id},'',0,NOW())");
            if (!$owner_logged) {
                mysqli_rollback($conn);
                return array('ok' => false, 'insufficient' => false, 'balance' => intval($buyer['points']));
            }
        }
    }
    mysqli_commit($conn);
    pd_sync_user_group($buyer_id);
    if ($owner_id > 0) pd_sync_user_group($owner_id);
    return array('ok' => true, 'charged' => true, 'balance' => $new_buyer_balance);
}

function pd_attachment_url($id) {
    return pd_url_page('download.php', array('id' => intval($id)));
}

// 每次首下载扣的积分（0 = 不扣费）
function pd_download_points_cost() {
    return max(0, intval(pd_setting('download_points_cost', 0)));
}

// 将正文里引用的、当前用户的孤儿附件（thread_id=0）绑定到帖子/回复。
// AJAX 预上传（api/upload-attachment.php）会先写入 thread_id=0 的附件并在正文插入下载链接，
// 发帖/回复/编辑保存时调用本函数把它们正式归属到帖子。
function pd_bind_content_attachments($thread_id, $post_id, $user_id, $content) {
    $thread_id = intval($thread_id);
    $user_id = intval($user_id);
    if ($thread_id <= 0 || $user_id <= 0) return 0;
    // 匹配 /download/123 或 download.php?id=123
    if (!preg_match_all('#download(?:\.php\?id=|/)(\d+)#i', (string)$content, $m)) return 0;
    $ids = array();
    foreach ($m[1] as $x) { $x = intval($x); if ($x > 0) $ids[$x] = $x; }
    if (!$ids) return 0;
    $in = implode(',', $ids);
    $post_id = intval($post_id);
    $ok = mysqli_query(db(), "UPDATE pd_attachments SET thread_id={$thread_id}, post_id={$post_id} WHERE id IN ({$in}) AND user_id={$user_id} AND thread_id=0");
    return $ok ? mysqli_affected_rows(db()) : 0;
}

// 清理长时间未绑定的孤儿附件（预上传后未发帖），删本地文件 + 记录
function pd_cleanup_orphan_attachments($hours = 24) {
    $hours = max(1, intval($hours));
    $rs = mysqli_query(db(), "SELECT id, file_path FROM pd_attachments WHERE thread_id=0 AND post_id=0 AND created_at < (NOW() - INTERVAL {$hours} HOUR) LIMIT 200");
    if (!$rs) return 0;
    $n = 0;
    while ($row = mysqli_fetch_assoc($rs)) {
        $p = (string)$row['file_path'];
        pd_delete_attachment_file($p);
        mysqli_query(db(), "DELETE FROM pd_attachments WHERE id=" . intval($row['id']));
        $n++;
    }
    return $n;
}

function pd_attachment_delete_form($att, $label = '删除附件') {
    if (!pd_can_delete_attachment($att)) {
        return '';
    }
    $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : pd_url_page('index.php');
    return '<form class="attachment-delete-form" method="post" action="' . h(pd_url_page('delete_attachment.php')) . '" data-confirm="确定删除这个附件？删除后服务器文件也会被删除。">'
        . pd_csrf_field()
        . '<input type="hidden" name="id" value="' . intval($att['id']) . '">'
        . '<input type="hidden" name="redirect" value="' . h($redirect) . '">'
        . '<button class="action-badge action-badge-danger" type="submit" title="' . h($label) . '" aria-label="' . h($label) . '" data-tooltip="' . h($label) . '"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span>' . h($label) . '</span></button>'
        . '</form>';
}

function pd_upload_max_mb() {
    return pd_setting_int('upload_max_mb', 5, 1, 50);
}

function pd_upload_allowed_exts() {
    $raw = strtolower(pd_setting('upload_allowed_exts', 'jpg,jpeg,png,gif,webp,zip,rar'));
    $parts = preg_split('/[\s,，|]+/', $raw);
    $exts = array();
    $blocked = array('php', 'phtml', 'pht', 'phar', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'zsh', 'htaccess', 'userini');
    foreach ($parts as $ext) {
        $ext = trim($ext);
        $ext = ltrim($ext, '.');
        if ($ext !== '' && preg_match('/^[a-z0-9]+$/', $ext) && !in_array($ext, $blocked, true)) {
            $exts[] = $ext;
        }
    }
    $exts = array_values(array_unique($exts));
    if (empty($exts)) {
        $exts = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar');
    }
    return $exts;
}

function pd_s3_enabled() {
    return intval(pd_setting('s3_enabled', '0')) === 1;
}

function pd_s3_setting($key, $default = '') {
    return trim((string)pd_setting($key, $default));
}

function pd_s3_key($safe_name) {
    $prefix = trim(pd_s3_setting('s3_path_prefix', 'litebbs'), "/ \t\n\r\0\x0B");
    $prefix = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $prefix);
    $date_path = date('Y/m/d');
    return ($prefix !== '' ? $prefix . '/' : '') . $date_path . '/' . ltrim($safe_name, '/');
}

function pd_s3_public_url($key) {
    $cdn = rtrim(pd_s3_setting('s3_cdn_domain', ''), '/');
    if ($cdn !== '') {
        return $cdn . '/' . ltrim($key, '/');
    }
    $endpoint = rtrim(pd_s3_setting('s3_endpoint', ''), '/');
    $bucket = pd_s3_setting('s3_bucket', '');
    return $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
}

function pd_s3_private_ref($key) {
    return 's3-private://' . ltrim((string)$key, '/');
}

function pd_s3_private_key($ref) {
    $prefix = 's3-private://';
    return strpos((string)$ref, $prefix) === 0 ? substr((string)$ref, strlen($prefix)) : '';
}

function pd_s3_presigned_download_url($key, $expires = 120) {
    $endpoint = rtrim(pd_s3_setting('s3_endpoint', ''), '/');
    $bucket = pd_s3_setting('s3_bucket', '');
    $region = pd_s3_setting('s3_region', 'auto');
    $access_key = pd_s3_setting('s3_access_key', '');
    $secret_key = pd_s3_setting('s3_secret_key', '');
    if ($endpoint === '' || $bucket === '' || $access_key === '' || $secret_key === '') return '';

    $url = $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim((string)$key, '/')));
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || empty($parts['path'])) return '';
    $host = $parts['host'] . (!empty($parts['port']) ? ':' . intval($parts['port']) : '');
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $params = array(
        'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
        'X-Amz-Credential' => $access_key . '/' . $scope,
        'X-Amz-Date' => $amz_date,
        'X-Amz-Expires' => (string)max(30, min(900, intval($expires))),
        'X-Amz-SignedHeaders' => 'host',
    );
    ksort($params, SORT_STRING);
    $canonical_query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $canonical_request = "GET\n" . $parts['path'] . "\n" . $canonical_query . "\nhost:" . $host . "\n\nhost\nUNSIGNED-PAYLOAD";
    $string_to_sign = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $scope . "\n" . hash('sha256', $canonical_request);
    $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret_key, true);
    $k_region = hash_hmac('sha256', $region, $k_date, true);
    $k_service = hash_hmac('sha256', 's3', $k_region, true);
    $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
    $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
    return $url . '?' . $canonical_query . '&X-Amz-Signature=' . $signature;
}

function pd_s3_upload_bytes($body, $key, $content_type, &$error) {
    if (!function_exists('curl_init')) {
        $error = '服务器未开启 PHP cURL，无法上传到 S3/R2。';
        return '';
    }
    $endpoint = rtrim(pd_s3_setting('s3_endpoint', ''), '/');
    $bucket = pd_s3_setting('s3_bucket', '');
    $region = pd_s3_setting('s3_region', 'auto');
    $access_key = pd_s3_setting('s3_access_key', '');
    $secret_key = pd_s3_setting('s3_secret_key', '');
    if ($endpoint === '' || $bucket === '' || $region === '' || $access_key === '' || $secret_key === '') {
        $error = 'S3/R2 配置不完整，请填写 Endpoint、Bucket、Region、Access Key 和 Secret Key。';
        return '';
    }
    if (!preg_match('/^https?:\/\//i', $endpoint)) {
        $error = 'S3/R2 Endpoint 必须以 http:// 或 https:// 开头。';
        return '';
    }
    $url = $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
    $url_parts = parse_url($url);
    if (empty($url_parts['host']) || empty($url_parts['path'])) {
        $error = 'S3/R2 Endpoint 无效。';
        return '';
    }
    $host = $url_parts['host'];
    if (!empty($url_parts['port'])) {
        $host .= ':' . $url_parts['port'];
    }
    $canonical_uri = $url_parts['path'];
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $payload_hash = hash('sha256', $body);
    $service = 's3';
    $credential_scope = $date_stamp . '/' . $region . '/' . $service . '/aws4_request';
    $canonical_headers = 'content-type:' . $content_type . "\n"
        . 'host:' . $host . "\n"
        . 'x-amz-content-sha256:' . $payload_hash . "\n"
        . 'x-amz-date:' . $amz_date . "\n";
    $signed_headers = 'content-type;host;x-amz-content-sha256;x-amz-date';
    $canonical_request = "PUT\n" . $canonical_uri . "\n\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
    $string_to_sign = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $credential_scope . "\n" . hash('sha256', $canonical_request);
    $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret_key, true);
    $k_region = hash_hmac('sha256', $region, $k_date, true);
    $k_service = hash_hmac('sha256', $service, $k_region, true);
    $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
    $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $access_key . '/' . $credential_scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: ' . $authorization,
        'Content-Type: ' . $content_type,
        'Host: ' . $host,
        'X-Amz-Content-Sha256: ' . $payload_hash,
        'X-Amz-Date: ' . $amz_date
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $response = curl_exec($ch);
    $http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $http_code < 200 || $http_code >= 300) {
        $error = 'S3/R2 上传失败：' . ($curl_error !== '' ? $curl_error : ('HTTP ' . $http_code . ' ' . $response));
        return '';
    }
    return pd_s3_public_url($key);
}

function pd_s3_upload_file($tmp_name, $key, $content_type, &$error) {
    $body = @file_get_contents($tmp_name);
    if ($body === false) {
        $error = '读取上传文件失败。';
        return '';
    }
    return pd_s3_upload_bytes($body, $key, $content_type, $error);
}

function pd_s3_upload_private_file($tmp_name, $key, $content_type, &$error) {
    $url = pd_s3_upload_file($tmp_name, $key, $content_type, $error);
    return $url === '' ? '' : pd_s3_private_ref($key);
}

function pd_s3_delete_private_ref($ref, &$error = '') {
    $key = pd_s3_private_key($ref);
    if ($key === '') return false;
    if (!function_exists('curl_init')) {
        $error = '服务器未开启 PHP cURL。';
        return false;
    }
    $endpoint = rtrim(pd_s3_setting('s3_endpoint', ''), '/');
    $bucket = pd_s3_setting('s3_bucket', '');
    $region = pd_s3_setting('s3_region', 'auto');
    $access_key = pd_s3_setting('s3_access_key', '');
    $secret_key = pd_s3_setting('s3_secret_key', '');
    if ($endpoint === '' || $bucket === '' || $access_key === '' || $secret_key === '') return false;
    $url = $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || empty($parts['path'])) return false;
    $host = $parts['host'] . (!empty($parts['port']) ? ':' . intval($parts['port']) : '');
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $payload_hash = hash('sha256', '');
    $scope = $date_stamp . '/' . $region . '/s3/aws4_request';
    $canonical_headers = 'host:' . $host . "\n" . 'x-amz-content-sha256:' . $payload_hash . "\n" . 'x-amz-date:' . $amz_date . "\n";
    $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
    $canonical_request = "DELETE\n" . $parts['path'] . "\n\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
    $string_to_sign = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $scope . "\n" . hash('sha256', $canonical_request);
    $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret_key, true);
    $k_region = hash_hmac('sha256', $region, $k_date, true);
    $k_service = hash_hmac('sha256', 's3', $k_region, true);
    $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
    $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $access_key . '/' . $scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $authorization,
            'Host: ' . $host,
            'X-Amz-Content-Sha256: ' . $payload_hash,
            'X-Amz-Date: ' . $amz_date,
        ),
    ));
    $response = curl_exec($ch);
    $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false || ($status !== 204 && ($status < 200 || $status >= 300))) {
        $error = $curl_error !== '' ? $curl_error : 'HTTP ' . $status;
        return false;
    }
    return true;
}

function pd_render_ad($position) {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_ads'");
    if (!$table || mysqli_num_rows($table) == 0) {
        return '';
    }
    $pos = esc($position);
    $rs = mysqli_query(db(), "SELECT * FROM pd_ads WHERE position='{$pos}' AND is_enabled=1 LIMIT 1");
    $ad = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$ad || $ad['image_path'] === '') {
        return '';
    }
    $style = '';
    if ($ad['width'] !== '') {
        $style .= 'width:' . h($ad['width']) . ';';
    }
    if ($ad['height'] !== '') {
        $style .= 'height:' . h($ad['height']) . ';';
    }
    $img = '<img src="' . h($ad['image_path']) . '" alt="' . h($ad['title']) . '" style="' . $style . '">';
    if ($ad['link_url'] !== '') {
        $img = '<a href="' . h($ad['link_url']) . '" target="_blank" rel="noopener">' . $img . '</a>';
    }
    return '<div class="ad-box ad-' . h($position) . '">' . $img . '</div>';
}

function pd_upload_attachments($thread_id, $post_id, $user_id, &$errors) {
    if (empty($_FILES['attachments']) || !is_array($_FILES['attachments']['name'])) {
        return 0;
    }

    $has_file = false;
    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
        if ($_FILES['attachments']['name'][$i] !== '') {
            $has_file = true;
            break;
        }
    }
    if (!$has_file) {
        return 0;
    }

    $retry_after = 0;
    if (!pd_rate_limit_allow('upload-user', intval($user_id), 60, 3600, $retry_after)) {
        $errors[] = '上传过于频繁，请稍后再试。';
        return 0;
    }

    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_attachments'");
    if (!$table || mysqli_num_rows($table) == 0) {
        $errors[] = '附件表不存在，请先访问 install/upgrade.php 升级数据库。';
        return 0;
    }

    $allow_exts = pd_upload_allowed_exts();
    $use_remote = pd_s3_enabled();
    $upload_dir = PD_ROOT . '/uploads';
    if (!$use_remote) {
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $errors[] = 'uploads 目录创建失败，请检查目录权限。';
            return 0;
        }
        if (!is_writable($upload_dir)) {
            $errors[] = 'uploads 目录不可写，请把目录权限设置为可写。';
            return 0;
        }
    }

    $saved = 0;
    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
        if ($_FILES['attachments']['name'][$i] === '') {
            continue;
        }
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = $_FILES['attachments']['name'][$i] . ' 上传失败，错误码：' . intval($_FILES['attachments']['error'][$i]);
            continue;
        }
        $max_mb = pd_upload_max_mb();
        if ($_FILES['attachments']['size'][$i] > $max_mb * 1024 * 1024) {
            $errors[] = $_FILES['attachments']['name'][$i] . ' 超过 ' . $max_mb . 'MB，已跳过。';
            continue;
        }
        $original = $_FILES['attachments']['name'][$i];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allow_exts)) {
            $errors[] = $original . ' 格式不支持。';
            continue;
        }
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp')) && @getimagesize($_FILES['attachments']['tmp_name'][$i]) === false) {
            $errors[] = $original . ' 不是有效图片文件。';
            continue;
        }
        $safe_name = pd_random_upload_name($ext);
        if ($use_remote) {
            $remote_error = '';
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
                $content_type = function_exists('mime_content_type') ? mime_content_type($_FILES['attachments']['tmp_name'][$i]) : 'application/octet-stream';
                $file_path = pd_remote_upload_file($_FILES['attachments']['tmp_name'][$i], $safe_name, $content_type, $remote_error);
            } else {
                $key = pd_s3_key($safe_name);
                $file_path = pd_s3_upload_private_file($_FILES['attachments']['tmp_name'][$i], $key, 'application/octet-stream', $remote_error);
            }
            if ($file_path === '') {
                $errors[] = $original . ' ' . $remote_error;
                continue;
            }
        } else {
            pd_ensure_upload_protection();
            if (!pd_store_uploaded_attachment_file($_FILES['attachments']['tmp_name'][$i], $ext, $file_path)) {
                $errors[] = $original . ' 保存失败，请检查 uploads 权限。';
                continue;
            }
        }
        $path_sql = esc($file_path);
        $original_sql = esc($original);
        $ext_sql = esc($ext);
        $size = intval($_FILES['attachments']['size'][$i]);
        $thread_id = intval($thread_id);
        $post_id = intval($post_id);
        $user_id = intval($user_id);
        $ok = mysqli_query(db(), "INSERT INTO pd_attachments (thread_id,post_id,user_id,file_path,original_name,file_ext,file_size,created_at) VALUES ({$thread_id},{$post_id},{$user_id},'{$path_sql}','{$original_sql}','{$ext_sql}',{$size},NOW())");
        if ($ok) {
            $saved++;
        } else {
            $errors[] = $original . ' 数据保存失败：' . mysqli_error(db());
        }
    }
    return $saved;
}
