<?php
// LiteBBS profile page build: 2026-06-15 with password_confirm
require_once __DIR__ . '/../functions.php';
qf_ensure_account_auth_schema();
$u = require_login();
$u = current_user();

// AJAX：预览随机卡通头像（不落库，仅用于“换一个”实时预览）
if (isset($_GET['ajax']) && $_GET['ajax'] === 'avatar_cartoon') {
    $seed = isset($_GET['seed']) ? preg_replace('/[^a-zA-Z0-9]/', '', (string)$_GET['seed']) : '';
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: no-store');
    echo qf_cartoon_default_avatar_svg(intval($u['id']), $u['username'], $u['nickname'], $seed);
    exit;
}

$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = clean_text($_POST['nickname'], 30);
    $email = clean_text(isset($_POST['email']) ? $_POST['email'] : '', 190);
    $signature = clean_text(isset($_POST['signature']) ? $_POST['signature'] : '', 255);
    $gender = clean_text(isset($_POST['gender']) ? $_POST['gender'] : '', 10);
    $notification_sound_enabled = !empty($_POST['notification_sound_enabled']) ? 1 : 0;
    $password = (string)$_POST['password'];
    $password_confirm = (string)(isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '');
    $avatar_type = isset($_POST['avatar_type']) ? $_POST['avatar_type'] : '';
    if (!in_array($avatar_type, array('upload', 'gravatar', 'cartoon'), true)) {
        $avatar_type = '';
    }
    $avatar_path = $u['avatar'];
    if (!in_array($gender, array('', '男', '女', '保密'))) {
        $gender = '';
    }

    if ($nickname === '') {
        $error = '昵称不能为空。';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确。';
    } elseif ($email !== '') {
        $email_sql = esc($email);
        $email_user = mysqli_query(db(), "SELECT id FROM qf_users WHERE email='{$email_sql}' AND id<>" . intval($u['id']) . " LIMIT 1");
        if ($email_user && mysqli_num_rows($email_user) > 0) {
            $error = '这个邮箱已经被其他账号绑定。';
        }
    }

    // 头像：按用户选择的来源处理
    if ($error === '' && $avatar_type === 'gravatar') {
        if (!qf_avatar_gravatar_enabled()) {
            $error = 'Gravatar 头像已被管理员关闭。';
        } elseif ($email === '') {
            $error = '使用 Gravatar 头像需要先绑定邮箱。';
        } else {
            $avatar_path = '';
        }
    } elseif ($error === '' && $avatar_type === 'cartoon') {
        if (!qf_avatar_cartoon_enabled()) {
            $error = '随机卡通头像已被管理员关闭。';
        } else {
            $seed = isset($_POST['cartoon_seed']) ? preg_replace('/[^a-zA-Z0-9]/', '', (string)$_POST['cartoon_seed']) : '';
            $new_path = qf_save_chosen_cartoon(intval($u['id']), $u['username'], $u['nickname'], $seed);
            if ($new_path !== '') {
                $avatar_path = $new_path;
            } else {
                $error = '生成随机头像失败，请检查 assets/avatars 目录权限。';
            }
        }
    } elseif ($error === '' && ($avatar_type === 'upload' || $avatar_type === '')) {
        if (!empty($_FILES['avatar']['name'])) {
            if (!qf_avatar_upload_enabled()) {
                $error = '上传头像已被管理员关闭。';
            } elseif ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                if (!in_array($ext, $allowed)) {
                    $error = '头像只支持 jpg、jpeg、png、gif、webp。';
                } elseif (intval($_FILES['avatar']['size']) > 2 * 1024 * 1024) {
                    $error = '头像不能超过 2MB。';
                } else {
                    $dir = __DIR__ . '/../uploads/avatar';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $name = 'avatar_' . intval($u['id']) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . '/' . $name)) {
                        $avatar_path = 'uploads/avatar/' . $name;
                    } else {
                        $error = '头像上传失败，请检查 uploads/avatar 权限。';
                    }
                }
            } else {
                $error = '头像上传失败。';
            }
        } elseif ($avatar_type === 'upload' && !(isset($u['avatar']) && $u['avatar'] !== '' && !qf_is_generated_avatar_path($u['avatar']))) {
            $error = '请选择要上传的头像图片。';
        }
    }

    if ($error === '') {
        $nickname_sql = esc($nickname);
        $email_sql = esc($email);
        $email_bound_sql = $email === '' ? 'NULL' : ((!isset($u['email']) || $u['email'] !== $email) ? 'NOW()' : 'email_bound_at');
        $signature_sql = esc($signature);
        $gender_sql = esc($gender);
        $avatar_sql = esc($avatar_path);
        if ($password !== '') {
            if ($password !== $password_confirm) {
                $error = '两次输入的新密码不一致，请重新输入。';
            } elseif (strlen($password) < 6) {
                $error = '新密码至少 6 位。';
            } else {
                $password_sql = esc(qf_password_hash($password));
                mysqli_query(db(), "UPDATE qf_users SET nickname='{$nickname_sql}', email='{$email_sql}', email_bound_at={$email_bound_sql}, avatar='{$avatar_sql}', signature='{$signature_sql}', gender='{$gender_sql}', notification_sound_enabled={$notification_sound_enabled}, password='{$password_sql}' WHERE id=" . intval($u['id']));
                $saved = true;
            }
        } else {
            mysqli_query(db(), "UPDATE qf_users SET nickname='{$nickname_sql}', email='{$email_sql}', email_bound_at={$email_bound_sql}, avatar='{$avatar_sql}', signature='{$signature_sql}', gender='{$gender_sql}', notification_sound_enabled={$notification_sound_enabled} WHERE id=" . intval($u['id']));
            $saved = true;
        }
        $u = current_user();
    }
}

$passkeys = mysqli_query(db(), "SELECT * FROM qf_passkeys WHERE user_id=" . intval($u['id']) . " ORDER BY id DESC");
$page_title = '个人设置 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card narrow-card">
    <h1>个人设置</h1>
    <p>
        <a class="btn btn-light btn-small" href="<?php echo h(qf_url_user($u['id'])); ?>">查看个人主页</a>
        <a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('rankings.php')); ?>">用户排行榜</a>
    </p>
    <?php if ($saved) { ?><div class="alert success">资料已保存。</div><?php } ?>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>
    <?php
    $cur_avatar = isset($u['avatar']) ? (string)$u['avatar'] : '';
    $cur_email = isset($u['email']) ? trim((string)$u['email']) : '';
    if ($cur_avatar !== '' && !qf_is_generated_avatar_path($cur_avatar)) {
        $current_avatar_type = 'upload';
    } elseif (qf_is_chosen_cartoon_path($cur_avatar)) {
        $current_avatar_type = 'cartoon';
    } elseif ($cur_email !== '' && qf_avatar_gravatar_enabled()) {
        $current_avatar_type = 'gravatar';
    } else {
        $current_avatar_type = 'cartoon';
    }
    $avatar_enabled = array();
    if (qf_avatar_upload_enabled()) { $avatar_enabled[] = 'upload'; }
    if (qf_avatar_gravatar_enabled()) { $avatar_enabled[] = 'gravatar'; }
    if (qf_avatar_cartoon_enabled()) { $avatar_enabled[] = 'cartoon'; }
    if (!in_array($current_avatar_type, $avatar_enabled, true)) {
        $current_avatar_type = !empty($avatar_enabled) ? $avatar_enabled[0] : 'upload';
    }
    $cartoon_base_url = qf_url_page('profile.php');
    $cartoon_sep = (strpos($cartoon_base_url, '?') === false) ? '?' : '&';
    ?>
    <form method="post" enctype="multipart/form-data" x-data="{ atype: '<?php echo h($current_avatar_type); ?>', seed: '' }">
        <label>头像</label>
        <div class="profile-avatar-choose">
            <div class="profile-avatar-preview">
                <img src="<?php echo h(qf_user_avatar($u, 200)); ?>" alt="<?php echo h($u['nickname']); ?>" x-show="atype !== 'cartoon'"<?php if ($current_avatar_type === 'cartoon') echo ' style="display:none"'; ?>>
                <img alt="随机卡通头像预览" x-show="atype === 'cartoon'"<?php if ($current_avatar_type !== 'cartoon') echo ' style="display:none"'; ?> :src="'<?php echo h($cartoon_base_url . $cartoon_sep); ?>ajax=avatar_cartoon' + (seed ? '&seed=' + seed : '')">
            </div>
            <div class="profile-avatar-sources">
                <?php if (qf_avatar_upload_enabled()) { ?>
                    <label class="profile-avatar-radio"><input type="radio" name="avatar_type" value="upload" x-model="atype" <?php if ($current_avatar_type === 'upload') echo 'checked'; ?>> <span><i class="fa-solid fa-upload" aria-hidden="true"></i> 上传图片</span></label>
                <?php } ?>
                <?php if (qf_avatar_gravatar_enabled()) { ?>
                    <label class="profile-avatar-radio"><input type="radio" name="avatar_type" value="gravatar" x-model="atype" <?php if ($current_avatar_type === 'gravatar') echo 'checked'; ?>> <span><i class="fa-solid fa-envelope" aria-hidden="true"></i> Gravatar（邮箱）</span></label>
                <?php } ?>
                <?php if (qf_avatar_cartoon_enabled()) { ?>
                    <label class="profile-avatar-radio"><input type="radio" name="avatar_type" value="cartoon" x-model="atype" <?php if ($current_avatar_type === 'cartoon') echo 'checked'; ?>> <span><i class="fa-solid fa-face-smile" aria-hidden="true"></i> 随机卡通</span></label>
                <?php } ?>
            </div>
        </div>
        <?php if (qf_avatar_upload_enabled()) { ?>
        <div class="profile-avatar-panel" x-show="atype === 'upload'"<?php if ($current_avatar_type !== 'upload') echo ' style="display:none"'; ?>>
            <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp">
            <p class="muted">支持 jpg、jpeg、png、gif、webp，最大 2MB，保存后即生效。</p>
        </div>
        <?php } ?>
        <?php if (qf_avatar_gravatar_enabled()) { ?>
        <div class="profile-avatar-panel" x-show="atype === 'gravatar'"<?php if ($current_avatar_type !== 'gravatar') echo ' style="display:none"'; ?>>
            <p class="muted">使用绑定邮箱的 Gravatar 头像<?php echo $cur_email !== '' ? '（' . h($cur_email) . '）' : '，请先在下方绑定邮箱'; ?>。修改邮箱后保存即可更新。</p>
        </div>
        <?php } ?>
        <?php if (qf_avatar_cartoon_enabled()) { ?>
        <div class="profile-avatar-panel" x-show="atype === 'cartoon'"<?php if ($current_avatar_type !== 'cartoon') echo ' style="display:none"'; ?>>
            <input type="hidden" name="cartoon_seed" :value="seed">
            <button type="button" class="btn btn-light btn-small" @click="seed = (Date.now().toString(36) + Math.floor(Math.random() * 1000000).toString(36))"><i class="fa-solid fa-shuffle" aria-hidden="true"></i> 换一个</button>
            <p class="muted">点击“换一个”随机生成头像，满意后点击下方“保存资料”即可保存。</p>
        </div>
        <?php } ?>
        <label>昵称</label>
        <input type="text" name="nickname" maxlength="30" value="<?php echo h($u['nickname']); ?>" required>
        <label>绑定邮箱</label>
        <input type="email" name="email" maxlength="190" value="<?php echo h(isset($u['email']) ? $u['email'] : ''); ?>" placeholder="name@example.com" autocomplete="email">
        <p class="muted">绑定后可作为账号联系方式。当前没有启用邮件验证码，保存后立即绑定。</p>
        <label>个性签名</label>
        <textarea name="signature" rows="3" maxlength="255" placeholder="写一句展示自己的签名"><?php echo h(isset($u['signature']) ? $u['signature'] : ''); ?></textarea>
        <label>性别</label>
        <select name="gender">
            <?php $gender_value = isset($u['gender']) ? $u['gender'] : ''; ?>
            <option value="" <?php if ($gender_value === '') echo 'selected'; ?>>请选择</option>
            <option value="男" <?php if ($gender_value === '男') echo 'selected'; ?>>男</option>
            <option value="女" <?php if ($gender_value === '女') echo 'selected'; ?>>女</option>
            <option value="保密" <?php if ($gender_value === '保密') echo 'selected'; ?>>保密</option>
        </select>
        <label>消息语音提示</label>
        <label><input class="inline-check" type="checkbox" name="notification_sound_enabled" value="1" <?php if (qf_notification_sound_enabled($u)) echo 'checked'; ?>> 开启消息语音提示</label>
        <p class="muted">关闭后不会播放语音，消息铃铛仍然正常显示。</p>
        <label>新密码</label>
        <input type="password" name="password" placeholder="不修改请留空" autocomplete="new-password">
        <label>重复新密码 <span class="muted">（必须与上方一致）</span></label>
        <input type="password" name="password_confirm" placeholder="再次输入新密码以确认" autocomplete="new-password">
        <p class="muted">两次输入相同后才会保存新密码；两次都留空则不修改密码。</p>
        <button class="btn" type="submit">保存资料</button>
    </form>
</section>
<section class="card narrow-card passkey-card">
    <h2>Passkey 登录</h2>
    <p class="muted">添加后可以使用系统指纹、面容、锁屏密码或安全密钥登录这个账号。</p>
    <button class="btn" type="button" data-passkey-register><i class="fa-solid fa-key" aria-hidden="true"></i> 添加 Passkey</button>
    <div class="passkey-list">
        <?php $passkey_count = 0; while ($passkeys && $pk = mysqli_fetch_assoc($passkeys)) { $passkey_count++; ?>
            <div class="passkey-item">
                <div>
                    <strong><?php echo h($pk['label'] !== '' ? $pk['label'] : 'Passkey'); ?></strong>
                    <p class="muted">添加于 <?php echo format_time($pk['created_at']); ?><?php if (!empty($pk['last_used_at'])) { ?> · 最近使用 <?php echo format_time($pk['last_used_at']); ?><?php } ?></p>
                </div>
                <button class="action-badge action-badge-danger" type="button" title="删除" aria-label="删除" data-passkey-delete="<?php echo intval($pk['id']); ?>"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span>删除</span></button>
            </div>
        <?php } ?>
        <?php if ($passkey_count === 0) { ?><p class="muted">还没有添加 Passkey。</p><?php } ?>
    </div>
</section>
<?php qf_include_footer(); ?>
