<?php

// Prueba smoke de logos: valida que GD convierta a WebP y respete dimensiones.
require_once __DIR__ . '/../app/logo_upload.php';

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
    fail('La extension GD no esta disponible.');
}

$source = tempnam(sys_get_temp_dir(), 'logo_source_') . '.png';
$target = tempnam(sys_get_temp_dir(), 'logo_target_') . '.webp';

$image = imagecreatetruecolor(1600, 600);
imagealphablending($image, false);
imagesavealpha($image, true);
$transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
$blue = imagecolorallocate($image, 11, 114, 185);
imagefilledrectangle($image, 0, 0, 1600, 600, $transparent);
imagefilledrectangle($image, 120, 120, 1480, 480, $blue);
imagepng($image, $source);
imagedestroy($image);

resize_logo_image($source, $target);

$info = getimagesize($target);
if (!$info) {
    fail('No se genero una imagen valida.');
}

if (($info['mime'] ?? '') !== 'image/webp') {
    fail('La imagen optimizada no quedo en WebP.');
}

if ($info[0] > LOGO_OUTPUT_MAX_WIDTH || $info[1] > LOGO_OUTPUT_MAX_HEIGHT) {
    fail('El logo optimizado excede las dimensiones esperadas.');
}

@unlink($source);
@unlink($target);

echo "OK: logo convertido a WebP y ajustado al encabezado." . PHP_EOL;
