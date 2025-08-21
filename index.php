<?php
require_once __DIR__ . '/token.php';
// Zugriff prüfen
checkAccess();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <title>Hochzeits-Galerie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="app">
    <h1>Willkommen zur Hochzeits-Galerie 🎉</h1>
<form id="uploadForm" enctype="multipart/form-data">
    <label for="fileInput" class="upload-label">Dateien auswählen</label>
    <input type="file" name="files[]" id="fileInput" multiple accept="image/*" hidden>
        <div id="selectedFiles" style="margin-top:10px;"></div>
    <button type="submit" style="margin-top:10px;">Hochladen</button>
</form>

<div id="uploadStatus" style="margin-top:15px;"></div>
  
    <div class="gallery" id="gallery"></div>
<!-- Lightbox -->
<div id="lightbox">
    <span id="prev">&#10094;</span>
    <img src="">
    <span id="next">&#10095;</span>
</div>

<!-- Galerie & Lightbox -->
<script src="js/gallery.js"></script>
<!-- Upload -->
<script src="js/upload.js"></script>
</body>
</html>
