<?php
// Secure session cookie parameters before starting the session
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0, // session cookie
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
require_once __DIR__ . '/config/config.php';

/**
 * Prüft, ob der Zugriff erlaubt ist.
 * Speichert den Token in der Session für 24 Stunden, wenn er korrekt übergeben wird.
 * Entfernt den Token anschließend aus der URL.
 */
function checkAccess() {
    // Token aus URL prüfen
    if (isset($_GET['token']) && $_GET['token'] !== '') {
        // Zugriffstoken muss in der Umgebung konfiguriert sein
        if (ACCESS_TOKEN !== '' && hash_equals(ACCESS_TOKEN, (string)$_GET['token'])) {
            // Session-Hardening
            session_regenerate_id(true);
            $_SESSION['access_granted'] = true;
            // 24 Stunden Gültigkeit (kann bei Bedarf via ENV konfigurierbar gemacht werden)
            $_SESSION['token_expires'] = time() + 24*60*60;

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
