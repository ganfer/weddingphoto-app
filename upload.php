<?php
require_once __DIR__ . '/token.php';
checkAccess();

$uploadDir = __DIR__ . '/upload/';
$imgDir = __DIR__ . '/img/';

const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

function createImageResourceFromData(string $path, string $mime) {
    switch ($mime) {
        case 'image/jpeg':
            return imagecreatefromjpeg($path);
        case 'image/png':
            return imagecreatefrompng($path);
        case 'image/gif':
            return imagecreatefromgif($path);
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null;
        default:
            return null;
    }
}

function saveReencodedImage($imgResource, string $destPath, string $mime): bool {
    // Re-encode to the same family; strip metadata implicitly
    switch ($mime) {
        case 'image/jpeg':
            return imagejpeg($imgResource, $destPath, 90);
        case 'image/png':
            return imagepng($imgResource, $destPath, 6);
        case 'image/gif':
            return imagegif($imgResource, $destPath);
        case 'image/webp':
            return function_exists('imagewebp') ? imagewebp($imgResource, $destPath, 90) : false;
        default:
            return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Keine Dateien ausgewählt']);
        exit;
    }

    $uploaded = [];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        // Basic upload errors and size checks
        if (!isset($_FILES['files']['error'][$i]) || $_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        if (!is_uploaded_file($tmp)) {
            continue;
        }
        $size = $_FILES['files']['size'][$i] ?? 0;
        if ($size <= 0 || $size > MAX_UPLOAD_BYTES) {
            continue;
        }

        $mime = finfo_file($finfo, $tmp) ?: '';
        $imgInfo = @getimagesize($tmp);
        if (!$imgInfo || empty($allowedMimes[$mime])) {
            continue;
        }

        $ext = $allowedMimes[$mime];

        // Dateiname: Datum + Uhrzeit
        $datePrefix = date('Ymd_His');
        $finalName = $datePrefix . '.' . $ext;

        // Konflikte vermeiden
        $counter = 1;
        while (file_exists($uploadDir . $finalName)) {
            $finalName = $datePrefix . '_' . $counter . '.' . $ext;
            $counter++;
        }

        // Re-encode image to drop metadata and enforce safe format
        $imgResource = createImageResourceFromData($tmp, $mime);
        if (!$imgResource) {
            continue;
        }

        $uploadPath = $uploadDir . $finalName;
        $imgPath = $imgDir . $finalName;

        $okUpload = saveReencodedImage($imgResource, $uploadPath, $mime);
        $okImg = $okUpload ? saveReencodedImage($imgResource, $imgPath, $mime) : false;
        imagedestroy($imgResource);

        if ($okUpload && $okImg) {
            $uploaded[] = $finalName;
        }
    }

    finfo_close($finfo);

    header('Content-Type: application/json');
    echo json_encode(['uploaded'=>$uploaded]);
    exit;
}
