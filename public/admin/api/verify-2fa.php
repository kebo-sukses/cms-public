<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/_totp.php';

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

require_once __DIR__ . '/_auth.php';
require_auth();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$secret = $input['secret'] ?? '';
$token = $input['token'] ?? '';

if (!$secret || !$token) fail('Missing parameters', 400);

if (!verify_totp($secret, $token)) fail('Invalid token', 401);

// Update users.json - set twoFactorEnabled true and secret
$path = __DIR__ . '/../../data/users.json';
if (!file_exists($path)) fail('users.json missing', 500);
$json = json_decode(file_get_contents($path), true);
if (!$json || !isset($json['users'][0])) fail('Invalid users.json', 500);
$json['users'][0]['twoFactorEnabled'] = true;
$json['users'][0]['twoFactorSecret'] = $secret;
if (file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
    fail('Failed to update users.json', 500);
}

echo json_encode(['success' => true, 'message' => '2FA enabled']);
?>
