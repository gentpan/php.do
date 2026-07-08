<?php
require_once __DIR__ . '/../functions.php';

pd_ensure_account_auth_schema();

function pd_passkey_options_for_user($user) {
    $allow = array();
    $uid = intval($user['id']);
    $rs = mysqli_query(db(), "SELECT credential_id, transports FROM pd_passkeys WHERE user_id={$uid} ORDER BY id ASC");
    while ($rs && $row = mysqli_fetch_assoc($rs)) {
        $item = array('type' => 'public-key', 'id' => $row['credential_id']);
        $transports = array_values(array_filter(array_map('trim', explode(',', (string)$row['transports']))));
        if ($transports) $item['transports'] = $transports;
        $allow[] = $item;
    }
    return $allow;
}

function pd_passkey_attested_credential($auth_data) {
    $info = pd_webauthn_auth_data_info($auth_data);
    if (($info['flags'] & 0x01) !== 0x01) {
        throw new Exception('请确认设备上的 Passkey 操作。');
    }
    if (($info['flags'] & 0x40) !== 0x40) {
        throw new Exception('Passkey 注册数据不完整。');
    }
    $offset = 37 + 16;
    if (strlen($auth_data) < $offset + 2) {
        throw new Exception('Passkey 凭据数据不完整。');
    }
    $len = unpack('n', substr($auth_data, $offset, 2));
    $credential_len = intval($len[1]);
    $offset += 2;
    $credential_id = substr($auth_data, $offset, $credential_len);
    $offset += $credential_len;
    $cose = substr($auth_data, $offset);
    if ($credential_id === '' || $cose === '') {
        throw new Exception('Passkey 凭据为空。');
    }
    pd_webauthn_public_key_pem($cose);
    return array(
        'credential_id' => $credential_id,
        'public_key_cose' => $cose,
        'sign_count' => intval($info['sign_count'])
    );
}

function pd_passkey_register_options() {
    $u = require_login();
    $challenge = pd_b64url_encode(random_bytes(32));
    $_SESSION['pd_passkey_register_challenge'] = $challenge;
    pd_json_response(array(
        'ok' => true,
        'publicKey' => array(
            'challenge' => $challenge,
            'rp' => array('name' => pd_site_name(), 'id' => pd_webauthn_rp_id()),
            'user' => array(
                'id' => pd_b64url_encode('user-' . intval($u['id'])),
                'name' => $u['username'],
                'displayName' => $u['nickname'] !== '' ? $u['nickname'] : $u['username']
            ),
            'pubKeyCredParams' => array(
                array('type' => 'public-key', 'alg' => -7),
                array('type' => 'public-key', 'alg' => -257)
            ),
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => pd_passkey_options_for_user($u),
            'authenticatorSelection' => array(
                'residentKey' => 'preferred',
                'userVerification' => 'preferred'
            )
        )
    ));
}

function pd_passkey_register_verify() {
    $u = require_login();
    $input = pd_json_input();
    $expected = isset($_SESSION['pd_passkey_register_challenge']) ? (string)$_SESSION['pd_passkey_register_challenge'] : '';
    unset($_SESSION['pd_passkey_register_challenge']);
    try {
        $raw_id = pd_b64url_decode(isset($input['rawId']) ? $input['rawId'] : '');
        $client_data = pd_b64url_decode(isset($input['clientDataJSON']) ? $input['clientDataJSON'] : '');
        $attestation_raw = pd_b64url_decode(isset($input['attestationObject']) ? $input['attestationObject'] : '');
        if ($raw_id === false || $client_data === false || $attestation_raw === false || $expected === '') {
            throw new Exception('Passkey 注册请求已过期。');
        }
        if (!pd_webauthn_verify_client($client_data, 'webauthn.create', $expected)) {
            throw new Exception('Passkey 注册来源验证失败。');
        }
        $attestation = pd_cbor_decode($attestation_raw);
        if (!is_array($attestation) || !isset($attestation['authData'])) {
            throw new Exception('Passkey 注册数据无法读取。');
        }
        $credential = pd_passkey_attested_credential($attestation['authData']);
        if (!hash_equals($raw_id, $credential['credential_id'])) {
            throw new Exception('Passkey 凭据不一致。');
        }
        $credential_id = esc(pd_b64url_encode($credential['credential_id']));
        $public_key = esc(pd_b64url_encode($credential['public_key_cose']));
        $label = clean_text(isset($input['label']) ? $input['label'] : '', 80);
        if ($label === '') $label = 'Passkey';
        $label_sql = esc($label);
        $transports = '';
        if (isset($input['transports']) && is_array($input['transports'])) {
            $transports = implode(',', array_slice(array_map('clean_text', $input['transports'], array_fill(0, count($input['transports']), 20)), 0, 8));
        }
        $transports_sql = esc($transports);
        $uid = intval($u['id']);
        $count = intval($credential['sign_count']);
        mysqli_query(db(), "INSERT INTO pd_passkeys (user_id, credential_id, public_key_cose, sign_count, label, transports, created_at) VALUES ({$uid}, '{$credential_id}', '{$public_key}', {$count}, '{$label_sql}', '{$transports_sql}', NOW()) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), public_key_cose=VALUES(public_key_cose), label=VALUES(label), transports=VALUES(transports)");
        pd_json_response(array('ok' => true, 'message' => 'Passkey 已添加。'));
    } catch (Throwable $e) {
        pd_json_response(array('ok' => false, 'error' => $e->getMessage()), 400);
    }
}

function pd_passkey_login_options() {
    $input = pd_json_input();
    $username = clean_text(isset($input['username']) ? $input['username'] : '', 30);
    if ($username === '') {
        pd_json_response(array('ok' => false, 'error' => '请先输入用户名。'), 400);
    }
    $username_sql = esc($username);
    $rs = mysqli_query(db(), "SELECT * FROM pd_users WHERE username='{$username_sql}' AND status=1 LIMIT 1");
    $u = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$u) {
        pd_json_response(array('ok' => false, 'error' => '没有找到这个用户。'), 404);
    }
    $allow = pd_passkey_options_for_user($u);
    if (!$allow) {
        pd_json_response(array('ok' => false, 'error' => '这个账号还没有绑定 Passkey。'), 404);
    }
    $challenge = pd_b64url_encode(random_bytes(32));
    $_SESSION['pd_passkey_login_challenge'] = $challenge;
    $_SESSION['pd_passkey_login_user'] = intval($u['id']);
    pd_json_response(array(
        'ok' => true,
        'publicKey' => array(
            'challenge' => $challenge,
            'rpId' => pd_webauthn_rp_id(),
            'allowCredentials' => $allow,
            'timeout' => 60000,
            'userVerification' => 'preferred'
        )
    ));
}

function pd_passkey_login_verify() {
    $input = pd_json_input();
    $expected = isset($_SESSION['pd_passkey_login_challenge']) ? (string)$_SESSION['pd_passkey_login_challenge'] : '';
    $expected_user = isset($_SESSION['pd_passkey_login_user']) ? intval($_SESSION['pd_passkey_login_user']) : 0;
    unset($_SESSION['pd_passkey_login_challenge'], $_SESSION['pd_passkey_login_user']);
    try {
        $raw_id_b64 = isset($input['rawId']) ? (string)$input['rawId'] : '';
        $client_data = pd_b64url_decode(isset($input['clientDataJSON']) ? $input['clientDataJSON'] : '');
        $auth_data = pd_b64url_decode(isset($input['authenticatorData']) ? $input['authenticatorData'] : '');
        $signature = pd_b64url_decode(isset($input['signature']) ? $input['signature'] : '');
        if ($client_data === false || $auth_data === false || $signature === false || $expected === '' || $expected_user < 1) {
            throw new Exception('Passkey 登录请求已过期。');
        }
        if (!pd_webauthn_verify_client($client_data, 'webauthn.get', $expected)) {
            throw new Exception('Passkey 登录来源验证失败。');
        }
        $credential_sql = esc($raw_id_b64);
        $rs = mysqli_query(db(), "SELECT p.*, u.status FROM pd_passkeys p LEFT JOIN pd_users u ON u.id=p.user_id WHERE p.credential_id='{$credential_sql}' LIMIT 1");
        $passkey = $rs ? mysqli_fetch_assoc($rs) : null;
        if (!$passkey || intval($passkey['user_id']) !== $expected_user || intval($passkey['status']) !== 1) {
            throw new Exception('Passkey 不属于这个账号。');
        }
        $info = pd_webauthn_auth_data_info($auth_data);
        if (($info['flags'] & 0x01) !== 0x01) {
            throw new Exception('请确认设备上的 Passkey 操作。');
        }
        $public_key_cose = pd_b64url_decode($passkey['public_key_cose']);
        if ($public_key_cose === false) {
            throw new Exception('Passkey 公钥读取失败。');
        }
        $pem = pd_webauthn_public_key_pem($public_key_cose);
        if ($pem === '') {
            throw new Exception('Passkey 公钥格式不支持。');
        }
        $verified = openssl_verify($auth_data . hash('sha256', $client_data, true), $signature, $pem, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new Exception('Passkey 签名验证失败。');
        }
        $pid = intval($passkey['id']);
        $counter = intval($info['sign_count']);
        mysqli_query(db(), "UPDATE pd_passkeys SET sign_count={$counter}, last_used_at=NOW() WHERE id={$pid}");
        session_regenerate_id(true);
        $_SESSION['pd_uid'] = intval($passkey['user_id']);
        pd_json_response(array('ok' => true, 'redirect' => pd_url_page('index.php')));
    } catch (Throwable $e) {
        pd_json_response(array('ok' => false, 'error' => $e->getMessage()), 400);
    }
}

function pd_passkey_delete() {
    $u = require_login();
    $input = pd_json_input();
    $id = intval(isset($input['id']) ? $input['id'] : 0);
    if ($id < 1) {
        pd_json_response(array('ok' => false, 'error' => '请选择要删除的 Passkey。'), 400);
    }
    mysqli_query(db(), "DELETE FROM pd_passkeys WHERE id={$id} AND user_id=" . intval($u['id']));
    pd_json_response(array('ok' => true, 'message' => 'Passkey 已删除。'));
}

$action = isset($_GET['action']) ? clean_text($_GET['action'], 30) : '';
if ($action === 'register-options') pd_passkey_register_options();
if ($action === 'register-verify') pd_passkey_register_verify();
if ($action === 'login-options') pd_passkey_login_options();
if ($action === 'login-verify') pd_passkey_login_verify();
if ($action === 'delete') pd_passkey_delete();
pd_json_response(array('ok' => false, 'error' => '未知 Passkey 操作。'), 404);
