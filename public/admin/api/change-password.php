<?php
session_start();
header('Content-Type: application/json');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

require_once __DIR__ . '/_auth.php';
require_auth();

// CSRF protection
require_once __DIR__ . '/csrf_helper.php';
require_csrf();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$current = $input['currentPassword'] ?? '';
$new = $input['newPassword'] ?? '';
if (!$current || !$new) fail('Missing parameters', 400);

$path = __DIR__ . '/../../data/users.json';
if (!file_exists($path)) fail('users.json missing', 500);
$json = json_decode(file_get_contents($path), true);
if (!$json || !isset($json['users'][0])) fail('Invalid users.json', 500);
$user = &$json['users'][0];

// Verify current password (helper will migrate legacy SHA-256 if needed)
require_once __DIR__ . '/_auth.php';
if (!verify_password_and_migrate($user, $current, $path)) {
    fail('Current password is incorrect', 401);
}

// Basic strength check
if (strlen($new) < 8) fail('New password too short', 400);

// Store new password with a modern hash
$user['password'] = password_hash($new, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
if (file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
    fail('Failed to update users.json', 500);
}

// Force logout so admin re-login
$_SESSION = [];
session_destroy();

echo json_encode(['success' => true, 'message' => 'Password changed; please login again']);
?>
