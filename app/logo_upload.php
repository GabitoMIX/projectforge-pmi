<?php

/**
 * Procesamiento seguro de logos del encabezado.
 *
 * El usuario sube PNG/JPG/WebP de cualquier tamano razonable, pero la app
 * guarda siempre una copia WebP pequena. Asi la plantilla no se deforma, la
 * web no carga archivos pesados y no dependemos de URLs externas.
 */

const LOGO_UPLOAD_PUBLIC_PATH = 'uploads/logos';
const LOGO_UPLOAD_MAX_BYTES = 2097152; // 2 MB por archivo antes de comprimir.
const LOGO_OUTPUT_MAX_WIDTH = 480;
const LOGO_OUTPUT_MAX_HEIGHT = 180;
const LOGO_OUTPUT_QUALITY = 82;

function process_logo_uploads(array $post, array $files): array
{
    $logos = [
        [
            'db_field' => 'header_left_logo',
            'file_field' => 'header_left_logo_file',
            'current_field' => 'current_header_left_logo',
            'remove_field' => 'remove_header_left_logo',
        ],
        [
            'db_field' => 'header_right_logo',
            'file_field' => 'header_right_logo_file',
            'current_field' => 'current_header_right_logo',
            'remove_field' => 'remove_header_right_logo',
        ],
    ];

    foreach ($logos as $logo) {
        $currentPath = normalize_logo_public_path($post[$logo['current_field']] ?? '');
        $file = $files[$logo['file_field']] ?? null;
        $removeRequested = isset($post[$logo['remove_field']]) && $post[$logo['remove_field']] === '1';

        $post[$logo['db_field']] = $currentPath;

        if (is_array($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $post[$logo['db_field']] = save_logo_upload($file, $currentPath);
            continue;
        }

        if ($removeRequested) {
            delete_uploaded_logo($currentPath);
            $post[$logo['db_field']] = '';
        }
    }

    return $post;
}

function normalize_logo_public_path(?string $path): string
{
    $path = str_replace('\\', '/', trim((string) $path));

    if ($path === '') {
        return '';
    }

    /*
     * Solo se aceptan logos generados por esta app. Esto evita guardar URLs,
     * rutas relativas raras o archivos que puedan ejecutar codigo.
     */
    $pattern = '#^' . preg_quote(LOGO_UPLOAD_PUBLIC_PATH, '#') . '/[a-f0-9]{32}\.webp$#';

    return preg_match($pattern, $path) === 1 ? $path : '';
}

function save_logo_upload(array $file, string $oldPath = ''): string
{
    validate_logo_upload($file);
    ensure_logo_upload_directory();

    $fileName = bin2hex(random_bytes(16)) . '.webp';
    $publicPath = LOGO_UPLOAD_PUBLIC_PATH . '/' . $fileName;
    $destination = logo_upload_directory() . DIRECTORY_SEPARATOR . $fileName;

    resize_logo_image((string) $file['tmp_name'], $destination);
    delete_uploaded_logo($oldPath);

    return $publicPath;
}

function validate_logo_upload(array $file): void
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(logo_upload_error_message($error));
    }

    if ((int)($file['size'] ?? 0) > LOGO_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('El logo supera 2 MB. Sube una imagen mas liviana.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('No se pudo validar el archivo subido.');
    }

    $imageInfo = @getimagesize($tmpName);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!$imageInfo || !in_array($imageInfo['mime'] ?? '', $allowedMimes, true)) {
        throw new RuntimeException('El logo debe ser PNG, JPG o WebP.');
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        throw new RuntimeException('La extension GD de PHP es necesaria para optimizar logos.');
    }
}

function resize_logo_image(string $sourcePath, string $destinationPath): void
{
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        throw new RuntimeException('No se pudo leer la imagen del logo.');
    }

    [$sourceWidth, $sourceHeight] = $imageInfo;
    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        throw new RuntimeException('El logo tiene dimensiones invalidas.');
    }

    $sourceBytes = file_get_contents($sourcePath);
    $sourceImage = $sourceBytes !== false ? @imagecreatefromstring($sourceBytes) : false;
    if (!$sourceImage) {
        throw new RuntimeException('No se pudo procesar el logo.');
    }

    $ratio = min(
        1,
        LOGO_OUTPUT_MAX_WIDTH / $sourceWidth,
        LOGO_OUTPUT_MAX_HEIGHT / $sourceHeight
    );
    $targetWidth = max(1, (int)round($sourceWidth * $ratio));
    $targetHeight = max(1, (int)round($sourceHeight * $ratio));

    $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);

    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
    imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);

    imagecopyresampled(
        $targetImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight
    );

    if (!imagewebp($targetImage, $destinationPath, LOGO_OUTPUT_QUALITY)) {
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        throw new RuntimeException('No se pudo guardar el logo optimizado.');
    }

    imagedestroy($sourceImage);
    imagedestroy($targetImage);
}

function delete_uploaded_logo(?string $publicPath): void
{
    $publicPath = normalize_logo_public_path($publicPath);

    if ($publicPath === '') {
        return;
    }

    $uploadDir = realpath(logo_upload_directory());
    $filePath = realpath(__DIR__ . '/../public/' . $publicPath);

    if ($uploadDir && $filePath && str_starts_with($filePath, $uploadDir) && is_file($filePath)) {
        unlink($filePath);
    }
}

function ensure_logo_upload_directory(): void
{
    $directory = logo_upload_directory();

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear la carpeta de logos.');
    }
}

function logo_upload_directory(): string
{
    return __DIR__ . '/../public/' . LOGO_UPLOAD_PUBLIC_PATH;
}

function logo_upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El logo supera el limite permitido.',
        UPLOAD_ERR_PARTIAL => 'El logo se subio incompleto. Intenta de nuevo.',
        UPLOAD_ERR_NO_FILE => 'No se recibio ningun logo.',
        default => 'No se pudo subir el logo.',
    };
}
