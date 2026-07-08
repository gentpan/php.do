<?php
require_once __DIR__ . '/../functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('404 Not Found');
}

$force = in_array('--force-generated', $argv, true);
$where = $force ? "avatar='' OR avatar LIKE 'assets/avatars/%.svg'" : "avatar=''";
$rs = mysqli_query(db(), "SELECT id, username, nickname, avatar FROM pd_users WHERE {$where}");
$count = 0;
while ($rs && $u = mysqli_fetch_assoc($rs)) {
    if ($force && $u['avatar'] !== '' && !pd_is_generated_avatar_path($u['avatar'])) {
        continue;
    }
    $avatar = pd_generate_default_avatar(intval($u['id']), $u['username'], $u['nickname']);
    if ($avatar !== '') {
        $avatar_sql = esc($avatar);
        mysqli_query(db(), "UPDATE pd_users SET avatar='{$avatar_sql}' WHERE id=" . intval($u['id']));
        $count++;
    }
}

echo "Backfilled avatars: {$count}\n";
