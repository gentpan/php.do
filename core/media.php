<?php
/* core/media.php — 由 functions.php 自动切分。集中 16 个定义。 */

function pd_add_user_points($user_id, $delta, $reason = '', $ref_type = '', $ref_id = 0, $note = '', $operator_id = 0) {
    pd_ensure_points_schema();
    $user_id = intval($user_id);
    $delta = intval($delta);
    if ($user_id <= 0 || $delta === 0) return false;
    mysqli_query(db(), "UPDATE pd_users SET points = GREATEST(0, points + ({$delta})) WHERE id={$user_id}");
    $rs = mysqli_query(db(), "SELECT points FROM pd_users WHERE id={$user_id} LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    $balance = $row ? intval($row['points']) : 0;
    $reason_sql = esc(clean_text($reason, 40));
    $ref_type_sql = esc(clean_text($ref_type, 20));
    $ref_id = intval($ref_id);
    $note_sql = esc(clean_text($note, 255));
    $operator_id = intval($operator_id);
    mysqli_query(db(), "INSERT INTO pd_points_log (user_id,delta,balance,reason,ref_type,ref_id,note,operator_id,created_at) VALUES ({$user_id},{$delta},{$balance},'{$reason_sql}','{$ref_type_sql}',{$ref_id},'{$note_sql}',{$operator_id},NOW())");
    pd_sync_user_group($user_id);
    return true;
}

function pd_admin_nav_items() {
    return array(
        array(
            'label' => '概览',
            'items' => array(
                array('title' => '版块与禁封', 'script' => 'index.php', 'icon' => 'fa-solid fa-gauge-high'),
                array('title' => '在线统计', 'script' => 'online.php', 'icon' => 'fa-solid fa-signal'),
            ),
        ),
        array(
            'label' => '内容与展示',
            'items' => array(
                array('title' => '站点设置', 'script' => 'settings.php', 'icon' => 'fa-solid fa-sliders'),
                array('title' => '主导航', 'script' => 'navs.php', 'icon' => 'fa-solid fa-bars'),
                array('title' => '广告位置', 'script' => 'ads.php', 'icon' => 'fa-solid fa-rectangle-ad'),
            ),
        ),
        array(
            'label' => '用户',
            'items' => array(
                array('title' => '用户管理', 'script' => 'users.php', 'icon' => 'fa-solid fa-users'),
                array('title' => '积分与等级', 'script' => 'points.php', 'icon' => 'fa-solid fa-star'),
                array('title' => '用户组', 'script' => 'groups.php', 'icon' => 'fa-solid fa-user-tag'),
                array('title' => '邀请码', 'script' => 'invites.php', 'icon' => 'fa-solid fa-ticket'),
            ),
        ),
        array(
            'label' => '系统',
            'items' => array(
                array('title' => '安全相关', 'script' => 'security.php', 'icon' => 'fa-solid fa-shield-halved'),
                array('title' => '社交登录', 'script' => 'social.php', 'icon' => 'fa-solid fa-right-to-bracket'),
                array('title' => '清理缓存', 'script' => 'cache.php', 'icon' => 'fa-solid fa-broom'),
            ),
        ),
    );
}

function pd_attachment_url($id) {
    return pd_url_page('download.php', array('id' => intval($id)));
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
    foreach ($parts as $ext) {
        $ext = trim($ext);
        $ext = ltrim($ext, '.');
        if ($ext !== '' && preg_match('/^[a-z0-9]+$/', $ext)) {
            $exts[] = $ext;
        }
    }
    $exts = array_values(array_unique($exts));
    if (empty($exts)) {
        $exts = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar');
    }
    return $exts;
}

function pd_upload_allowed_exts_label() {
    return implode('、', pd_upload_allowed_exts());
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

function pd_s3_test(&$message) {
    $key = pd_s3_key('test_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.txt');
    $error = '';
    $url = pd_s3_upload_bytes("LiteBBS S3/R2 test " . date('c') . "\n", $key, 'text/plain; charset=utf-8', $error);
    if ($url === '') {
        $message = $error;
        return false;
    }
    $message = 'S3/R2 测试上传成功：' . $url;
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
        $safe_name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        if ($use_remote) {
            $remote_error = '';
            $file_path = pd_remote_upload_file($_FILES['attachments']['tmp_name'][$i], $safe_name, 'application/octet-stream', $remote_error);
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
