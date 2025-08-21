<?php
require_once __DIR__ . '/token.php';
checkAccess();

$uploadDir = __DIR__ . '/upload/';
$imgDir = __DIR__ . '/img/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Keine Dateien ausgewählt']);
        exit;
    }

    $allowed = ['jpg','jpeg','png','gif'];
    $uploaded = [];

    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        $origName = $_FILES['files']['name'][$i];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) continue;

        // Datum + Uhrzeit als Dateiname
        $datePrefix = date('Ymd_His'); // z.B. 20250820_142530
        $finalName = $datePrefix . '.' . $ext;

        // Konflikte vermeiden
        $counter = 1;
        while (file_exists($uploadDir . $finalName)) {
            $finalName = $datePrefix . '_' . $counter . '.' . $ext;
            $counter++;
        }

        // Hochladen
        if (move_uploaded_file($tmp, $uploadDir . $finalName)) {
            // Kopie für Galerie
            copy($uploadDir . $finalName, $imgDir  . $finalName);

            $uploaded[] = $finalName;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['uploaded'=>$uploaded]);
    exit;
}
