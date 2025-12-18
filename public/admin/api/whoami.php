<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/_auth.php';

if (empty($_SESSION['calius_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = calius_get_current_user();
if (!$user) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'] ?? null,
        'username' => $user['username'] ?? null,
        'email' => $user['email'] ?? null,
        'role' => $user['role'] ?? 'admin',
        'permissions' => $user['permissions'] ?? []
    ]
    , 'csrfToken' => $_SESSION['csrf_token'] ?? null
]);

?>
