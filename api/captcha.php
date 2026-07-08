<?php
require_once __DIR__ . '/../functions.php';

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['qf_captcha_answer'] = $code;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$width = 132;
$height = 48;

$noise = '';
for ($i = 0; $i < 5; $i++) {
    $x1 = random_int(4, $width - 4);
    $y1 = random_int(4, $height - 4);
    $x2 = random_int(4, $width - 4);
    $y2 = random_int(4, $height - 4);
    $color = sprintf('#%02x%02x%02x', random_int(150, 200), random_int(160, 205), random_int(175, 220));
    $noise .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="' . random_int(1, 2) . '" opacity="0.65"/>';
}
for ($i = 0; $i < 28; $i++) {
    $noise .= '<circle cx="' . random_int(2, $width - 2) . '" cy="' . random_int(2, $height - 2) . '" r="' . (random_int(0, 1) ? 1 : 1.5) . '" fill="#' . dechex(random_int(9, 14)) . dechex(random_int(9, 14)) . dechex(random_int(10, 15)) . '" opacity="0.28"/>';
}

$letters = '';
for ($i = 0; $i < 4; $i++) {
    $x = 18 + $i * 28 + random_int(-2, 2);
    $y = 33 + random_int(-3, 3);
    $rot = random_int(-16, 16);
    $size = random_int(24, 28);
    $fill = sprintf('#%02x%02x%02x', random_int(28, 75), random_int(55, 110), random_int(95, 150));
    $letters .= '<text x="' . $x . '" y="' . $y . '" font-family="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace" font-size="' . $size . '" font-weight="700" fill="' . $fill . '" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')">' . h($code[$i]) . '</text>';
}

echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
    . '<defs><linearGradient id="cg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#f7fafc"/><stop offset="100%" stop-color="#eef3f8"/></linearGradient></defs>'
    . '<rect width="' . $width . '" height="' . $height . '" rx="6" fill="url(#cg)"/>'
    . '<rect x="1" y="1" width="' . ($width - 2) . '" height="' . ($height - 2) . '" rx="5" fill="none" stroke="#d8e3ec"/>'
    . $noise . $letters
    . '</svg>';
?>
