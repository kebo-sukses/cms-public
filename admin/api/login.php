<?php
session_start();
header('Content-Type: application/json');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$totp = $input['totp'] ?? null;

if (!$username || !$password) {
    fail('Username and password are required', 400);
}

$usersPath = __DIR__ . '/../../data/users.json';
if (!file_exists($usersPath)) fail('User data not found', 500);
$usersJson = @file_get_contents($usersPath);
if ($usersJson === false) fail('Failed to read users file', 500);
$data = json_decode($usersJson, true);
if (!$data || !isset($data['users']) || !is_array($data['users'])) fail('Invalid users.json', 500);

// Find user by username or email
$user = null;
foreach ($data['users'] as &$u) {
    if ((isset($u['username']) && $u['username'] === $username) || (isset($u['email']) && $u['email'] === $username)) {
        $user = &$u; break;
    }
}
if (!$user) fail('Invalid username or password', 401);

// Verify password using helper which also migrates legacy SHA-256 hashes
require_once __DIR__ . '/_auth.php';
if (!verify_password_and_migrate($user, $password, $usersPath)) {
    fail('Invalid username or password', 401);
}

// If user has 2FA enabled, require totp and verify server-side
if (!empty($user['twoFactorEnabled'])) {
    if (!$totp) {
        echo json_encode(['success' => true, 'requiresTwoFactor' => true, 'userId' => $user['id']]);
        exit;
    }
    // Verify TOTP
    require_once __DIR__ . '/_totp.php';
    $valid = verify_totp($user['twoFactorSecret'], $totp);
    if (!$valid) fail('Invalid authentication code', 401);
}

// If account requires password reset, issue a short-lived token and ask user to change
if (!empty($user['mustResetPassword'])) {
    require_once __DIR__ . '/password_reset_helper.php';
    $tok = create_password_reset_token($user['id']);
    echo json_encode(['success' => true, 'requiresPasswordChange' => true, 'token' => $tok, 'expiresIn' => 900]);
    exit;
}

// Authenticated: set session
// Regenerate session id to prevent fixation
session_regenerate_id(true);
$_SESSION['calius_admin'] = true;
$_SESSION['calius_user'] = [ 'id' => $user['id'], 'username' => $user['username'], 'role' => $user['role'] ?? 'admin' ];
// set CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

echo json_encode(['success' => true, 'message' => 'Login successful', 'csrfToken' => $_SESSION['csrf_token']]);
return;

?>
