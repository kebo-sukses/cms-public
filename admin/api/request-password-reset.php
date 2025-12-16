<?php
header('Content-Type: application/json');
require_once __DIR__ . '/password_reset_api_helper.php';

// Accept JSON or form POST
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = trim($input['username'] ?? '');
$providedKey = trim($input['emergencyKey'] ?? $_SERVER['HTTP_X_EMERGENCY_KEY'] ?? '');

if (!$username) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing username']);
    exit;
}

$result = request_password_reset_token($username, $providedKey, $_SERVER['REMOTE_ADDR'] ?? null);
if (!$result['success']) {
    http_response_code(400);
}
echo json_encode($result);
