<?php
require_once 'db.php';
session_start();


$captcha_code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
$_SESSION['captcha_code'] = $captcha_code;


header('Content-Type: image/png');
$image = imagecreatetruecolor(150, 50);


$start_color = imagecolorallocate($image, 240, 240, 240);
$end_color = imagecolorallocate($image, 200, 200, 200);
for ($i = 0; $i < 50; $i++) {
    $color = imagecolorallocate(
        $image,
        $start_color[0] + ($end_color[0] - $start_color[0]) * $i / 50,
        $start_color[1] + ($end_color[1] - $start_color[1]) * $i / 50,
        $start_color[2] + ($end_color[2] - $start_color[2]) * $i / 50
    );
    imageline($image, 0, $i, 150, $i, $color);
}

$text_color = imagecolorallocate($image, 50, 50, 50);


imagestring($image, 5, 35, 15, $captcha_code, $text_color);

for ($i = 0; $i < 100; $i++) {
    $noise_color = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));
    imagesetpixel($image, rand(0, 150), rand(0, 50), $noise_color);
}


imagepng($image);
imagedestroy($image);
?>
