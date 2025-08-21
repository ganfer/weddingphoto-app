<?php
session_start();
require_once __DIR__ . '/config/config.php';

/**
 * Prüft, ob der Zugriff erlaubt ist.
 * Speichert den Token in der Session für 7 Tage, wenn er korrekt übergeben wird.
 * Entfernt den Token anschließend aus der URL.
 */
function checkAccess() {
    // Token aus URL prüfen
    if (isset($_GET['token']) && $_GET['token'] === ACCESS_TOKEN) {
        $_SESSION['access_granted'] = true;
        $_SESSION['token_expires'] = time() + 7*24*60*60;

        // Token aus der URL entfernen und auf gleiche Seite weiterleiten
        $query = $_GET;
        unset($query['token']);
        $newUrl = $_SERVER['PHP_SELF'];
        if (!empty($query)) {
            $newUrl .= '?' . http_build_query($query);
        }
        header("Location: $newUrl");
        exit;
    }

    // Session prüfen
    if (empty($_SESSION['access_granted']) || !isset($_SESSION['token_expires'])) {
        redirectAccessDenied();
    }

    // Ablauf prüfen
    if (time() > $_SESSION['token_expires']) {
        session_unset();
        session_destroy();
        redirectAccessDenied();
    }
}

/**
 * Leitet zur access_denied.php weiter und beendet das Script
 */
function redirectAccessDenied() {
    if (file_exists(__DIR__ . '/access_denied.php')) {
        header("Location: access_denied.php");
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Kein Zugriff']);
    }
    exit;
}
