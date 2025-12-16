<?php
function get_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $body = null;
    // also accept JSON body field
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (is_array($input)) $body = $input['csrf_token'] ?? null;
    $token = $header ?? $body;
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}
