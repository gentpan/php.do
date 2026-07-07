<?php
// LiteBBS profile page build: 2026-06-15 with password_confirm
require_once __DIR__ . '/../functions.php';
qf_ensure_account_auth_schema();
$u = require_login();
$u = current_user();
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

    if ($error === '' && !empty($_FILES['avatar']['name'])) {
        if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
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
    <form method="post" enctype="multipart/form-data">
        <div class="profile-avatar-preview">
            <img src="<?php echo h(qf_user_avatar($u, 200)); ?>" alt="<?php echo h($u['nickname']); ?>">
        </div>
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
        <label>上传头像</label>
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp">
        <p class="muted">使用 HTML5 文件上传，不使用 Flash。支持 jpg、jpeg、png、gif、webp，最大 2MB。</p>
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
