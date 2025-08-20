<?php
header("Content-Type: text/plain"); // Für Statusmeldungen, JSON nur am Ende

// -----------------------------
// Konfiguration
// -----------------------------
$uploadDir = __DIR__ . '/upload/';
$cacheDir  = __DIR__ . '/img/';
$nextcloudUrl  = 'https://nextcloud.xxx.de/remote.php/dav/files/admin/Photos/';
$nextcloudUser = 'admin';
$nextcloudPass = 'xxx';

// Cache-Ordner erstellen, falls er nicht existiert
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

// -----------------------------
// 1) Upload aus Upload-Ordner
// -----------------------------
$files = array_filter(scandir($uploadDir), function($f) use ($uploadDir) {
    return !is_dir($uploadDir . $f) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $f);
});

foreach ($files as $file) {
    $localPath = $uploadDir . $file;
    $remoteUrl = $nextcloudUrl . rawurlencode($file);

    $ch = curl_init($remoteUrl);
    curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPass");
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, fopen($localPath, 'rb'));
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localPath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo "Fehler beim Upload von $file: $error\n";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        // Erfolgreich hochgeladen → Datei löschen
        unlink($localPath);
        echo "Erfolgreich hochgeladen: $file\n";
    } else {
        echo "Upload fehlgeschlagen für $file, HTTP-Code: $httpCode\n";
    }
}

// -----------------------------
// 2) Cache aktualisieren
// -----------------------------
$ch = curl_init($nextcloudUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PROPFIND");
curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Depth: 1"]);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo "Nextcloud konnte nicht erreicht werden\n";
    exit;
}

$xml = simplexml_load_string($response);
$xml->registerXPathNamespace("d", "DAV:");

$images = [];
$nextcloudFiles = [];

// Alle Bilder von Nextcloud durchgehen
foreach ($xml->xpath("//d:response") as $file) {
    $href = (string)$file->xpath("d:href")[0];
    $type = (string)$file->xpath("d:propstat/d:prop/d:getcontenttype")[0];

    if (str_starts_with($type, "image/")) {
        $filename = basename($href);
        $nextcloudFiles[] = $filename;

        $localFile = $cacheDir . $filename;

        // Bild nur herunterladen, wenn es noch nicht im Cache ist
        if (!file_exists($localFile)) {
            $imgUrl = $nextcloudUrl . rawurlencode($filename);
            $ch = curl_init($imgUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$nextcloudUser:$nextcloudPass");
            $imgData = curl_exec($ch);
            curl_close($ch);

            if ($imgData !== false) {
                file_put_contents($localFile, $imgData);
                echo "Cache aktualisiert: $filename\n";
            }
        }

        $images[] = 'cache/' . $filename;
    }
}

// Gelöschte Bilder im Cache entfernen (erst nach Cache-Aktualisierung)
foreach (scandir($cacheDir) as $file) {
    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif'])) {
        if (!in_array($file, $nextcloudFiles)) {
            unlink($cacheDir . $file);
            echo "Cache gelöscht: $file\n";
        }
    }
}

// JSON-Ausgabe der Cache-Dateien
echo "\nAktuelle Cache-Dateien:\n";
echo json_encode($images, JSON_PRETTY_PRINT);
