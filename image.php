<?php
session_start();
require_once __DIR__ . '/config/config.php';

$tokenValid = isset($_GET['token']) && $_GET['token'] === ACCESS_TOKEN;
if ($tokenValid) {
    setcookie("access_granted", "true", time() + 7*24*60*60, "/");
    $_SESSION['access_granted'] = true;
}

if (!isset($_SESSION['access_granted']) &&
    (!isset($_COOKIE['access_granted']) || $_COOKIE['access_granted'] !== "true")
) {
    http_response_code(403);
    exit("Kein Zugriff");
}

$cacheDir = __DIR__ . '/img/';
$file = basename($_GET['file'] ?? '');
$path = realpath($cacheDir . $file);

if ($path === false || strpos($path, realpath($cacheDir)) !== 0 || !file_exists($path)) {
    http_response_code(404);
    exit("Datei nicht gefunden");
}

header("Cache-Control: max-age=604800, public");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 604800) . " GMT");

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path);
finfo_close($finfo);

header("Content-Type: $mime");
readfile($path);
