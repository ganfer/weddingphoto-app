<?php
$uploadDir = __DIR__ . '/upload/';
$cacheDir  = __DIR__ . '/img/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
    // Originalname
    $name = basename($_FILES['files']['name'][$index]);
    
    // Alle Leer- und Sonderzeichen entfernen, nur Buchstaben, Zahlen, Punkt und Unterstrich behalten
    $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '', $name);
    
    $uploadPath = $uploadDir . $cleanName;
    $cachePath  = $cacheDir . $cleanName;

    if(move_uploaded_file($tmpName, $uploadPath)) {
        echo "$cleanName hochgeladen.<br>";
        if (!file_exists($cachePath)) {
            copy($uploadPath, $cachePath);
        }
    } else {
        echo "Fehler beim Hochladen von $cleanName.<br>";
    }
}
?>
