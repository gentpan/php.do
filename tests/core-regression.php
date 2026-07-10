<?php

$tmp = sys_get_temp_dir() . '/php-do-tests-' . bin2hex(random_bytes(6));
$private_tmp = $tmp . '-private';
mkdir($tmp . '/uploads', 0750, true);
define('PD_ROOT', $tmp);
define('PD_PRIVATE_STORAGE_PATH', $private_tmp);
mkdir(PD_PRIVATE_STORAGE_PATH . '/attachments', 0750, true);

$GLOBALS['pd_test_settings'] = array(
    'upload_allowed_exts' => 'jpg,png,zip,php,phtml,phar',
    's3_endpoint' => 'https://example.r2.cloudflarestorage.com',
    's3_bucket' => 'private-bucket',
    's3_region' => 'auto',
    's3_access_key' => 'test-access',
    's3_secret_key' => 'test-secret',
);

function pd_setting($key, $default = '') {
    return array_key_exists($key, $GLOBALS['pd_test_settings']) ? $GLOBALS['pd_test_settings'][$key] : $default;
}

function pd_setting_int($key, $default, $min = null, $max = null) {
    $value = intval(pd_setting($key, $default));
    if ($min !== null) $value = max($min, $value);
    if ($max !== null) $value = min($max, $value);
    return $value;
}

require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/media.php';
require_once __DIR__ . '/../core/util.php';
require_once __DIR__ . '/../core/schema.php';
require_once __DIR__ . '/../core/mail.php';

function expect_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$allowed = pd_upload_allowed_exts();
expect_true(in_array('jpg', $allowed, true) && in_array('zip', $allowed, true), 'safe upload extensions remain allowed');
expect_true(!in_array('php', $allowed, true) && !in_array('phtml', $allowed, true) && !in_array('phar', $allowed, true), 'executable upload extensions are always blocked');

$name_a = pd_random_upload_name('jpg');
$name_b = pd_random_upload_name('jpg');
expect_true($name_a !== $name_b && preg_match('/^[0-9]{14}_[a-f0-9]{24}\.jpg$/', $name_a) === 1, 'upload names are random and well formed');

$ref = pd_s3_private_ref('forum/secret.zip');
expect_true(pd_s3_private_key($ref) === 'forum/secret.zip', 'private S3 references round-trip');
$signed = pd_s3_presigned_download_url('forum/secret.zip', 120);
expect_true(strpos($signed, 'X-Amz-Signature=') !== false && strpos($signed, 'private-bucket/forum/secret.zip') !== false, 'private S3 download URLs are signed');

$local = PD_PRIVATE_STORAGE_PATH . '/attachments/test.dat';
file_put_contents($local, 'test');
expect_true(pd_resolve_local_attachment_file('private://test.dat') === realpath($local), 'private local attachments resolve outside the public root');
$storage_error = '';
expect_true(pd_prepare_private_attachment_storage($storage_error), 'private attachment storage is writable and outside the public root');
file_put_contents($tmp . '/outside.dat', 'no');
expect_true(pd_resolve_local_attachment_file('outside.dat') === false, 'attachment path traversal outside allowed roots is rejected');

$retry = 0;
expect_true(pd_rate_limit_allow('test', 'user', 2, 60, $retry), 'first rate-limited request is allowed');
expect_true(pd_rate_limit_allow('test', 'user', 2, 60, $retry), 'second rate-limited request is allowed');
expect_true(!pd_rate_limit_allow('test', 'user', 2, 60, $retry) && $retry > 0, 'excess rate-limited request is rejected');
pd_rate_limit_clear('test', 'user');

expect_true(pd_webauthn_counter_valid(0, 0), 'counterless authenticators remain supported');
expect_true(pd_webauthn_counter_valid(4, 5), 'increasing WebAuthn counters are accepted');
expect_true(!pd_webauthn_counter_valid(5, 5) && !pd_webauthn_counter_valid(5, 4), 'replayed WebAuthn counters are rejected');
expect_true(pd_cdata_text('a]]>b') === 'a]]]]><![CDATA[>b', 'RSS CDATA terminators are split safely');
expect_true(pd_valid_nav_url('/pages/about.php') && pd_valid_nav_url('https://example.com'), 'safe internal and HTTPS navigation URLs are accepted');
expect_true(!pd_valid_nav_url('//evil.example') && !pd_valid_nav_url('javascript:alert(1)'), 'protocol-relative and script navigation URLs are rejected');
$safe_svg = pd_sanitize_nav_svg('<svg onload="alert(1)" viewBox="0 0 10 10"><foreignObject><div>bad</div></foreignObject><path d="M0 0L1 1"/></svg>');
expect_true(strpos($safe_svg, 'onload') === false && stripos($safe_svg, 'foreignObject') === false && strpos($safe_svg, '<path') !== false, 'custom navigation SVG is reduced to a safe tag and attribute set');

$_SESSION = array('email_code_reset' => array('email' => 'a@example.com', 'code' => '123456', 'expires' => time() + 60, 'attempts' => 5));
expect_true(!pd_email_code_verify('a@example.com', '123456', 'reset') && empty($_SESSION['email_code_reset']), 'email codes lock after five attempts');

@unlink($local);
@unlink($tmp . '/outside.dat');
@rmdir($tmp . '/storage/rate-limits');
@rmdir($tmp . '/storage');
@rmdir($tmp . '/uploads');
@rmdir(PD_PRIVATE_STORAGE_PATH . '/attachments');
@rmdir(PD_PRIVATE_STORAGE_PATH);
@rmdir($tmp);

echo "core regression tests passed\n";
