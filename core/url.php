<?php
/* core/url.php — 由 functions.php 自动切分。集中 14 个定义。 */

function pd_base_href() {
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = str_replace('\\', '/', dirname($script_name));
    if (in_array(basename($dir), array('admin', 'api', 'pages'), true)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
        return '/';
    }
    return rtrim($dir, '/') . '/';
}

function pd_rewrite_enabled() {
    return intval(pd_setting('rewrite_enabled', '0')) === 1;
}

function pd_route_script($script, &$params = array()) {
    $map = array(
        'ad.php' => 'api/ad.php',
        'captcha.php' => 'api/captcha.php',
        'ajax_upload_image.php' => 'api/upload-image.php',
        'ajax_upload_attachment.php' => 'api/upload-attachment.php',
        'markdown_preview.php' => 'api/markdown-preview.php',
        'delete_attachment.php' => 'api/delete-attachment.php',
        'floor_reply.php' => 'api/floor-reply.php',
        'moderator_action.php' => 'api/moderator.php',
        'passkey.php' => 'api/passkey.php',
        'reply.php' => 'api/reply.php',
        'signin.php' => 'api/signin.php',
        'react.php' => 'api/react.php',
        'geoip.php' => 'api/geoip.php',
        'login.php' => 'pages/login.php',
        'logout.php' => 'api/auth.php',
        'register.php' => 'pages/register.php',
        'download.php' => 'api/download.php',
        'edit_thread.php' => 'pages/edit-thread.php',
        'forum.php' => 'pages/forum.php',
        'move_thread.php' => 'pages/move-thread.php',
        'notifications.php' => 'pages/notifications.php',
        'messages.php' => 'pages/messages.php',
        'page.php' => 'pages/page.php',
        'post.php' => 'pages/post.php',
        'profile.php' => 'pages/profile.php',
        'rankings.php' => 'pages/rankings.php',
        'search.php' => 'pages/search.php',
        'thread.php' => 'pages/thread.php',
        'user.php' => 'pages/user.php',
    );
    if ($script === 'logout.php' && !isset($params['action'])) {
        $params['action'] = 'logout';
    }
    return isset($map[$script]) ? $map[$script] : $script;
}

function pd_url_page($script, $params = array(), $fragment = '') {
    $script = ltrim((string)$script, '/');
    $params = is_array($params) ? $params : array();
    $logical_script = $script;
    $script = pd_route_script($script, $params);
    if (!pd_rewrite_enabled()) {
        return pd_append_url_parts($script, $params, $fragment);
    }
    if ($script === 'index.php' || $script === '') {
        return pd_append_url_parts('/', $params, $fragment);
    }
    if (strpos($script, 'api/') === 0 || strpos($script, 'admin/') === 0) {
        return pd_append_url_parts('/' . $script, $params, $fragment);
    }
    if (($logical_script === 'thread.php' || $script === 'pages/thread.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return pd_append_url_parts('/thread/' . $id . '.html', $params, $fragment);
    }
    if (($logical_script === 'forum.php' || $script === 'pages/forum.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        $slug = pd_forum_slug_by_id($id);
        return pd_append_url_parts('/' . ($slug !== '' ? $slug : 'forum/' . $id), $params, $fragment);
    }
    if (($logical_script === 'user.php' || $script === 'pages/user.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return pd_append_url_parts('/user/' . $id . '.html', $params, $fragment);
    }
    if (($logical_script === 'page.php' || $script === 'pages/page.php') && isset($params['slug'])) {
        $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$params['slug']));
        unset($params['slug']);
        return pd_append_url_parts('/' . $slug . '.php', $params, $fragment);
    }
    // 关于页规范地址保留 .php 扩展名（与 /rules.php、/help.php 等静态页一致）
    if ($logical_script === 'about.php' || $script === 'pages/about.php') {
        return pd_append_url_parts('/about.php', $params, $fragment);
    }
    if (($logical_script === 'download.php' || $script === 'api/download.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return pd_append_url_parts('/download/' . $id, $params, $fragment);
    }
    $clean = pd_clean_route_path($script);
    if ($clean !== $script) {
        return pd_append_url_parts('/' . $clean, $params, $fragment);
    }
    if (substr($script, -4) === '.php') {
        $script = substr($script, 0, -4);
    }
    return pd_append_url_parts('/' . $script, $params, $fragment);
}

function pd_url_nav($url) {
    $url = trim((string)$url);
    if ($url === '' || preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $url) || strpos($url, '//') === 0 || strpos($url, '#') === 0) {
        return $url;
    }
    $parts = parse_url($url);
    if (!$parts || !isset($parts['path'])) {
        return $url;
    }
    $path = ltrim($parts['path'], '/');
    if (substr($path, -4) !== '.php') {
        return $url;
    }
    $params = array();
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $params);
    }
    $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
    return pd_url_page($path, $params, $fragment);
}

function pd_url_thread($id) {
    $id = intval($id);
    return pd_rewrite_enabled() ? '/thread/' . $id . '.html' : pd_url_page('thread.php', array('id' => $id));
}

function pd_url_forum($id) {
    $id = intval($id);
    return pd_url_page('forum.php', array('id' => $id));
}

function pd_url_user($id) {
    return pd_url_page('user.php', array('id' => intval($id)));
}

function pd_url_messages($thread_id = 0, $to_user_id = 0) {
    $params = array();
    if ($thread_id > 0) {
        $params['thread'] = intval($thread_id);
    } elseif ($to_user_id > 0) {
        $params['to'] = intval($to_user_id);
    }
    return pd_url_page('messages.php', $params);
}

function pd_handle_oauth_action() {
    $provider = isset($_GET['provider']) ? preg_replace('/[^a-z]/', '', $_GET['provider']) : '';
    $action = isset($_GET['action']) ? clean_text($_GET['action'], 20) : 'start';
    $providers = pd_oauth_providers();
    if (!isset($providers[$provider]) || !pd_oauth_enabled($provider)) {
        $_SESSION['auth_error'] = '该第三方登录未启用。';
        redirect(pd_url_page('login.php'));
    }
    $client_id = trim(pd_setting('oauth_' . $provider . '_client_id', ''));
    $client_secret = trim(pd_setting('oauth_' . $provider . '_client_secret', ''));

    if ($action === 'start') {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => pd_oauth_redirect_uri($provider),
            'scope' => $providers[$provider]['scope'],
            'state' => $state,
            'response_type' => 'code',
        );
        if ($provider === 'google') {
            $params['access_type'] = 'online';
            $params['prompt'] = 'select_account';
        }
        redirect($providers[$provider]['authorize'] . '?' . http_build_query($params));
    }

    $state = isset($_GET['state']) ? (string)$_GET['state'] : '';
    if (empty($_SESSION['oauth_state']) || !hash_equals((string)$_SESSION['oauth_state'], $state)) {
        unset($_SESSION['oauth_state']);
        $_SESSION['auth_error'] = '登录校验失败（state 不匹配），请重试。';
        redirect(pd_url_page('login.php'));
    }
    unset($_SESSION['oauth_state']);

    $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
    if ($code === '') {
        $_SESSION['auth_error'] = '第三方未返回授权码，登录取消。';
        redirect(pd_url_page('login.php'));
    }

    $token_resp = pd_http_request('POST', $providers[$provider]['token'], array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'redirect_uri' => pd_oauth_redirect_uri($provider),
        'grant_type' => 'authorization_code',
    ), array('Accept: application/json'));
    $token_data = json_decode($token_resp['body'], true);
    $access_token = is_array($token_data) && isset($token_data['access_token']) ? $token_data['access_token'] : '';
    if ($access_token === '') {
        $_SESSION['auth_error'] = '获取访问令牌失败，请检查后台的 Client ID/Secret。';
        redirect(pd_url_page('login.php'));
    }

    $provider_uid = '';
    $login = '';
    $name = '';
    $email = '';
    if ($provider === 'github') {
        $ures = pd_http_request('GET', 'https://api.github.com/user', null, array(
            'Authorization: Bearer ' . $access_token,
            'User-Agent: php.do',
            'Accept: application/json',
        ));
        $profile = json_decode($ures['body'], true);
        if (is_array($profile) && isset($profile['id'])) {
            $provider_uid = (string)$profile['id'];
            $login = isset($profile['login']) ? $profile['login'] : '';
            $name = isset($profile['name']) && $profile['name'] !== '' ? $profile['name'] : $login;
            $email = isset($profile['email']) && $profile['email'] !== null ? $profile['email'] : '';
        }
        if ($provider_uid !== '' && $email === '') {
            $eres = pd_http_request('GET', 'https://api.github.com/user/emails', null, array(
                'Authorization: Bearer ' . $access_token,
                'User-Agent: php.do',
                'Accept: application/json',
            ));
            $emails = json_decode($eres['body'], true);
            if (is_array($emails)) {
                foreach ($emails as $em) {
                    if (!empty($em['primary']) && !empty($em['email'])) {
                        $email = $em['email'];
                        break;
                    }
                }
            }
        }
    } elseif ($provider === 'google') {
        $ures = pd_http_request('GET', 'https://openidconnect.googleapis.com/v1/userinfo', null, array(
            'Authorization: Bearer ' . $access_token,
        ));
        $profile = json_decode($ures['body'], true);
        if (is_array($profile) && isset($profile['sub'])) {
            $provider_uid = (string)$profile['sub'];
            $name = isset($profile['name']) ? $profile['name'] : '';
            $email = isset($profile['email']) ? $profile['email'] : '';
            $login = $email !== '' ? explode('@', $email)[0] : ('g' . $provider_uid);
        }
    }

    if ($provider_uid === '') {
        $_SESSION['auth_error'] = '读取第三方账号信息失败，请重试。';
        redirect(pd_url_page('login.php'));
    }

    $user_id = pd_oauth_login_or_register($provider, $provider_uid, $login, $name, $email);
    if ($user_id > 0) {
        session_regenerate_id(true);
        $_SESSION['pd_uid'] = $user_id;
        redirect(pd_url_page('index.php'));
    }
    $_SESSION['auth_error'] = '第三方登录失败，请稍后重试。';
    redirect(pd_url_page('login.php'));
}

function pd_handle_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(pd_url_page('login.php'));
    }
    $username_raw = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $username = esc($username_raw);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $rs = mysqli_query(db(), "SELECT * FROM pd_users WHERE username='{$username}' AND status=1 LIMIT 1");
    $u = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($u && pd_password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['pd_uid'] = intval($u['id']);
        redirect(pd_url_page('index.php'));
    }
    $_SESSION['auth_error'] = '用户名或密码错误。';
    $_SESSION['auth_login_username'] = $username_raw;
    redirect(pd_url_page('login.php'));
}

function pd_handle_register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(pd_url_page('register.php'));
    }
    $username = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $nickname = clean_text(isset($_POST['nickname']) ? $_POST['nickname'] : '', 30);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $invite_code = clean_text(isset($_POST['invite_code']) ? $_POST['invite_code'] : '', 32);
    $error = '';
    if (pd_captcha_required('register') && !pd_verify_captcha()) {
        $error = '验证码错误，请重新输入。';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = '用户名只能使用字母、数字、下划线，长度 3-30。';
    } elseif ($nickname === '' || strlen($password) < 6) {
        $error = '昵称不能为空，密码至少 6 位。';
    } elseif (pd_require_invite() && !pd_invite_valid($invite_code)) {
        $error = '邀请码无效或已被使用，请检查后重试。';
    } else {
        $daily_limit = intval(pd_setting('register_ip_daily_limit', '5'));
        if ($daily_limit < 1) {
            $daily_limit = 5;
        }
        $ip_raw = client_ip();
        $ip_check = esc($ip_raw);
        $today_count = count_rows("SELECT COUNT(*) FROM pd_users WHERE ip='{$ip_check}' AND created_at >= CURDATE()");
        if ($today_count >= $daily_limit) {
            $error = '当前 IP 今天注册次数已达到上限。';
        } else {
            $u = esc($username);
            $n = esc($nickname);
            $p = pd_password_hash($password);
            $ip = esc($ip_raw);
            if (mysqli_query(db(), "INSERT INTO pd_users (username,password,nickname,ip,created_at) VALUES ('{$u}','{$p}','{$n}','{$ip}',NOW())")) {
                $new_user_id = intval(mysqli_insert_id(db()));
                $avatar = pd_generate_default_avatar($new_user_id, $username, $nickname);
                if ($avatar !== '') {
                    $avatar_sql = esc($avatar);
                    mysqli_query(db(), "UPDATE pd_users SET avatar='{$avatar_sql}' WHERE id={$new_user_id}");
                }
                if (pd_require_invite()) {
                    pd_consume_invite($invite_code, $new_user_id);
                }
                session_regenerate_id(true);
                $_SESSION['pd_uid'] = $new_user_id;
                redirect(pd_url_page('index.php'));
            }
            $error = '注册失败，用户名可能已存在。';
        }
    }
    $_SESSION['auth_error'] = $error;
    $_SESSION['auth_register_username'] = $username;
    $_SESSION['auth_register_nickname'] = $nickname;
    redirect(pd_url_page('register.php'));
}

function pd_handle_logout() {
    unset($_SESSION['pd_uid']);
    session_regenerate_id(true);
    redirect(pd_url_page('index.php'));
}

function pd_handle_auth_action() {
    $action = isset($_GET['action']) ? clean_text($_GET['action'], 20) : '';
    if ($action === '' && isset($_POST['action'])) {
        $action = clean_text($_POST['action'], 20);
    }
    if ($action === 'login') {
        pd_handle_login();
    } elseif ($action === 'register') {
        pd_handle_register();
    } elseif ($action === 'logout') {
        pd_handle_logout();
    }
    redirect(pd_url_page('index.php'));
}
