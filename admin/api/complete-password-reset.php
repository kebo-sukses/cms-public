<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/password_reset_helper.php';

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$token = $input['token'] ?? '';
$new = $input['newPassword'] ?? '';
if (!$token || !$new) fail('Missing parameters', 400);

$userId = validate_and_consume_password_reset_token($token);
if (!$userId) fail('Invalid or expired token', 400);

$path = __DIR__ . '/../../data/users.json';
if (!file_exists($path)) fail('users.json missing', 500);
$json = json_decode(file_get_contents($path), true);
if (!$json || !isset($json['users']) || !is_array($json['users'])) fail('Invalid users.json', 500);

$found = false;
foreach ($json['users'] as &$u) {
    if (($u['id'] ?? '') === $userId) {
        // Change password and clear mustResetPassword
        if (strlen($new) < 8) fail('New password too short', 400);
        $u['password'] = password_hash($new, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
        unset($u['mustResetPassword']);
        $found = true;
        break;
    }
}
if (!$found) fail('User not found', 404);

if (file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
    fail('Failed to update users.json', 500);
}

// Log user in (create session) and provide CSRF token
session_regenerate_id(true);
$_SESSION['calius_admin'] = true;
// populate session with user details
foreach ($json['users'] as $u) {
    if (($u['id'] ?? '') === $userId) {
        $_SESSION['calius_user'] = [ 'id' => $u['id'], 'username' => $u['username'] ?? null, 'role' => $u['role'] ?? 'admin' ];
        break;
    }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

echo json_encode(['success' => true, 'message' => 'Password updated', 'csrfToken' => $_SESSION['csrf_token']]);

?>
