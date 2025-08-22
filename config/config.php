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

define('ACCESS_TOKEN', $envAccessToken);
define('NEXTCLOUD_URL', $envNextcloudUrl);
define('NEXTCLOUD_USER', $envNextcloudUser);
define('NEXTCLOUD_PASS', $envNextcloudPass);
