<?php
require_once __DIR__ . '/token.php';
checkAccess(); // Session prüfen

$imgDir = __DIR__ . '/img/';

// Alle Bilddateien suchen
$imgFiles = glob($imgDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

// Falls keine Bilder gefunden
if (!$imgFiles) {
    header('Content-Type: application/json');
    echo json_encode([], JSON_PRETTY_PRINT);
    exit;
}

// ETag & Last-Modified basierend auf allen Bildern
$etag = md5(implode('', array_map('filemtime', $imgFiles)));
$lastModified = gmdate('D, d M Y H:i:s', max(array_map('filemtime', $imgFiles))) . ' GMT';

// Prüfen, ob der Client schon die aktuelle Version hat
if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastModified)) {
    http_response_code(304); // Not Modified
    exit;
}

header("Content-Type: application/json");
header("Cache-Control: public, max-age=3600"); // 1 Stunde
header("ETag: $etag");
header("Last-Modified: $lastModified");

// Bilderliste bauen
$images = [];
foreach ($imgFiles as $file) {
    $images[] = [
        'src' => 'img.php?file=' . urlencode(basename($file)),
        'filename' => basename($file),
    ];
}


usort($images, function($a, $b) {
    return strcmp($b['filename'], $a['filename']);
});


// JSON ausgeben
echo json_encode($images, JSON_PRETTY_PRINT);
