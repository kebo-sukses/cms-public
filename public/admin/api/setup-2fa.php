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

// Generate base32 secret
$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
$secret = '';
for ($i = 0; $i < 32; $i++) $secret .= $chars[random_int(0, strlen($chars)-1)];

$issuer = 'Calius Digital';
$account = $_SESSION['calius_user']['email'] ?? $_SESSION['calius_user']['username'];
$otpauth = sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s', rawurlencode($issuer), rawurlencode($account), $secret, rawurlencode($issuer));
$qr = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . rawurlencode($otpauth);

echo json_encode(['success' => true, 'secret' => $secret, 'qr' => $qr]);
?>
