<?php
require_once __DIR__ . '/token.php';
checkAccess(); // Session prüfen

$fileParam = $_GET['file'] ?? '';
if (!$fileParam) {
    http_response_code(400);
    exit("Kein Dateiname angegeben");
}

$imgDir = __DIR__ . '/img/';
$path = null;

// Bilddateien durchsuchen (case-insensitive)
foreach (glob($imgDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE) as $f) {
    if (strcasecmp(basename($f), $fileParam) === 0) {
        $path = $f;
        break;
    }
}

if (!$path || !file_exists($path)) {
    http_response_code(404);
    exit("Datei nicht gefunden");
}

// MIME-Type bestimmen
$mime = mime_content_type($path);

// Header: Caching komplett verhindern
header("Content-Type: $mime");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Bild direkt ausgeben
readfile($path);
