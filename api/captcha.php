<?php
require_once __DIR__ . '/../functions.php';

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 5; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['qf_captcha_answer'] = $code;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$lines = '';
for ($i = 0; $i < 7; $i++) {
    $x1 = random_int(0, 160);
    $y1 = random_int(0, 52);
    $x2 = random_int(0, 160);
    $y2 = random_int(0, 52);
    $color = sprintf('#%02x%02x%02x', random_int(80, 180), random_int(110, 190), random_int(130, 210));
    $lines .= '<polyline points="' . $x1 . ',' . $y1 . ' ' . $x2 . ',' . $y2 . '" stroke="' . $color . '" stroke-width="' . random_int(1, 3) . '" fill="none" opacity="0.55"/>';
}

$dots = '';
for ($i = 0; $i < 45; $i++) {
    $dots .= '<circle cx="' . random_int(0, 160) . '" cy="' . random_int(0, 52) . '" r="' . random_int(1, 2) . '" fill="#' . dechex(random_int(8, 15)) . dechex(random_int(8, 15)) . dechex(random_int(8, 15)) . '" opacity="0.35"/>';
}

$letters = '';
for ($i = 0; $i < strlen($code); $i++) {
    $x = 18 + $i * 27 + random_int(-3, 3);
    $y = 34 + random_int(-5, 5);
    $rot = random_int(-20, 20);
    $size = random_int(24, 30);
    $fill = sprintf('#%02x%02x%02x', random_int(20, 70), random_int(55, 105), random_int(90, 145));
    $letters .= '<text x="' . $x . '" y="' . $y . '" font-family="Georgia,Times New Roman,serif" font-size="' . $size . '" font-weight="700" fill="' . $fill . '" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')">' . h($code[$i]) . '</text>';
}

echo '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="52" viewBox="0 0 160 52">'
    . '<rect width="160" height="52" rx="8" fill="#f5f9fc"/>'
    . '<path d="M0 34 C30 18, 52 48, 82 28 S132 14, 160 30" stroke="#d7e5ef" stroke-width="8" fill="none" opacity="0.7"/>'
    . $dots . $lines . $letters
    . '</svg>';
?>
