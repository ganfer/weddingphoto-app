<?php
// Load sensitive configuration from environment variables. Do not hardcode secrets here.
// ACCESS_TOKEN is required for gated access. If empty, access will always be denied.

$envAccessToken = getenv('ACCESS_TOKEN') ?: '';
$envNextcloudUrl = getenv('NEXTCLOUD_URL') ?: '';
$envNextcloudUser = getenv('NEXTCLOUD_USER') ?: '';
$envNextcloudPass = getenv('NEXTCLOUD_PASS') ?: '';

if ($envNextcloudUrl !== '') {
    // Normalize to end with a single trailing slash
    $envNextcloudUrl = rtrim($envNextcloudUrl, '/') . '/';
}

// Optional secrets override file: config/secrets.php should return an associative array
// e.g. [ 'ACCESS_TOKEN' => '...', 'NEXTCLOUD_URL' => '...', 'NEXTCLOUD_USER' => '...', 'NEXTCLOUD_PASS' => '...' ]
$secretsPath = __DIR__ . '/secrets.php';
if (file_exists($secretsPath)) {
    $overrides = include $secretsPath;
    if (is_array($overrides)) {
        $envAccessToken  = $overrides['ACCESS_TOKEN']   ?? $envAccessToken;
        if (!empty($overrides['NEXTCLOUD_URL'])) {
            $envNextcloudUrl = rtrim($overrides['NEXTCLOUD_URL'], '/') . '/';
        }
        $envNextcloudUser = $overrides['NEXTCLOUD_USER'] ?? $envNextcloudUser;
        $envNextcloudPass = $overrides['NEXTCLOUD_PASS'] ?? $envNextcloudPass;
    }
}

define('ACCESS_TOKEN', $envAccessToken);
define('NEXTCLOUD_URL', $envNextcloudUrl);
define('NEXTCLOUD_USER', $envNextcloudUser);
define('NEXTCLOUD_PASS', $envNextcloudPass);
