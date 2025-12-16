<?php
require_once __DIR__ . '/password_reset_helper.php';

function request_password_reset_token($usernameOrEmail, $providedKey, $remoteIp = null) {
    $remoteIp = $remoteIp ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    // Load settings (respect CMS_DATA_DIR when available)
    $dataDir = (function_exists('cms_data_dir') ? cms_data_dir() : (__DIR__ . '/../../data'));
    $settingsPath = $dataDir . '/settings.json';
    $settings = [];
    if (file_exists($settingsPath)) $settings = json_decode(@file_get_contents($settingsPath), true) ?: [];

    $emergencyKey = trim($settings['security']['emergencyResetKey'] ?? '');
    if ($emergencyKey === '') {
        return ['success' => false, 'message' => 'Emergency reset is not enabled on this server'];
    }

    if (!hash_equals($emergencyKey, (string)$providedKey)) {
        return ['success' => false, 'message' => 'Invalid emergency key'];
    }

    // Rate limit per IP (max 5 per hour)
    $rateFile = $dataDir . '/password_reset_requests.json';
    $rates = [];
    if (file_exists($rateFile)) $rates = json_decode(@file_get_contents($rateFile), true) ?: [];
    $now = time();
    $window = 3600; // 1 hour
    $limit = intval($settings['security']['emergencyResetLimitPerHour'] ?? 5);
    $rates[$remoteIp] = array_filter($rates[$remoteIp] ?? [], function($t) use ($now, $window) { return ($now - $t) < $window; });
    if (count($rates[$remoteIp]) >= $limit) {
        return ['success' => false, 'message' => 'Too many requests from this IP, try later'];
    }

    // find user by username or email
    $usersPath = $dataDir . '/users.json';
    if (!file_exists($usersPath)) return ['success' => false, 'message' => 'User data not available'];
    $users = json_decode(@file_get_contents($usersPath), true) ?: [];
    $foundUser = null;
    foreach ($users['users'] as $u) {
        if ((isset($u['username']) && $u['username'] === $usernameOrEmail) || (isset($u['email']) && $u['email'] === $usernameOrEmail) || (isset($u['id']) && $u['id'] === $usernameOrEmail)) {
            $foundUser = $u; break;
        }
    }
    if (!$foundUser) return ['success' => false, 'message' => 'User not found'];

    // create token
    $token = create_password_reset_token($foundUser['id']);

    // record rate
    $rates[$remoteIp][] = $now;
    @file_put_contents($rateFile, json_encode($rates, JSON_PRETTY_PRINT));

    return ['success' => true, 'token' => $token, 'expiresIn' => 900, 'userId' => $foundUser['id']];
}
