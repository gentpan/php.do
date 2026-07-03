<?php
require_once __DIR__ . '/../functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('404 Not Found');
}

$rs = mysqli_query(db(), "SELECT id, username, nickname FROM qf_users WHERE avatar=''");
$count = 0;
while ($rs && $u = mysqli_fetch_assoc($rs)) {
    $avatar = qf_generate_default_avatar(intval($u['id']), $u['username'], $u['nickname']);
    if ($avatar !== '') {
        $avatar_sql = esc($avatar);
        mysqli_query(db(), "UPDATE qf_users SET avatar='{$avatar_sql}' WHERE id=" . intval($u['id']));
        $count++;
    }
}

echo "Backfilled avatars: {$count}\n";
