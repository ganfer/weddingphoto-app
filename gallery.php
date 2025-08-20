<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['access_granted']) &&
    (!isset($_COOKIE['access_granted']) || $_COOKIE['access_granted'] !== "true")
) {
    http_response_code(403);
    exit(json_encode(["error" => "Kein Zugriff"]));
}

$cacheDir = __DIR__ . '/img/';
$images = [];

// Alle Bilddateien sammeln
foreach (scandir($cacheDir) as $file) {
    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif'])) {
        $path = $cacheDir . $file;
        $timestamp = filemtime($path); // Standard: Dateisystemdatum

        // EXIF-Datum auslesen (nur bei JPEG)
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg','jpeg'])) {
            $exif = @exif_read_data($path);
            if (!empty($exif['DateTimeOriginal'])) {
                $date = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
                if ($date !== false) {
                    $timestamp = $date->getTimestamp();
                }
            }
        }

        $images[] = [
            "src" => '/img/' . urlencode($file),
            "link" => '/img/' . urlencode($file),
            "timestamp" => $timestamp
        ];
    }
}

// Nach Timestamp absteigend sortieren (neueste zuerst)
usort($images, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

// Timestamp nicht mehr ausgeben
$images = array_map(fn($img) => ["src" => $img["src"], "link" => $img["link"]], $images);

header('Content-Type: application/json');
echo json_encode($images);
