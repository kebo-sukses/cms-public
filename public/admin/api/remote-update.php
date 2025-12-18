<?php
// remote-update.php
// Simple webhook receiver stub for remote template updates.
// Verifies HMAC signature (sha256) and enqueues received update metadata for review.

header('Content-Type: application/json');

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$secret = getenv('REMOTE_UPDATE_SECRET') ?: (defined('REMOTE_UPDATE_SECRET') ? REMOTE_UPDATE_SECRET : null);
if (!$secret) {
    respond(500, ['success' => false, 'message' => 'Server webhook secret not configured']);
}

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
if (!$sig) respond(400, ['success' => false, 'message' => 'Missing signature header']);

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $sig)) {
    respond(401, ['success' => false, 'message' => 'Invalid signature']);
}

$data = json_decode($payload, true);
if (!$data) respond(400, ['success' => false, 'message' => 'Invalid JSON payload']);

$logPath = __DIR__ . '/../../data/remote_updates.json';
$existing = [];
if (file_exists($logPath)) {
    $existing = json_decode(@file_get_contents($logPath), true) ?: [];
}

$entry = [
    'received_at' => gmdate('c'),
    'payload' => $data,
    'status' => 'received'
];
$existing[] = $entry;
@file_put_contents($logPath, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Respond accepted - actual apply is out-of-scope for this stub
respond(202, ['success' => true, 'message' => 'Update received', 'entry' => $entry]);

?>
