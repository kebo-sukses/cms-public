<?php
function create_password_reset_token($userId, $ttl = 900) {
    $store = __DIR__ . '/../../data/password_reset_tokens.json';
    $tokens = [];
    if (file_exists($store)) $tokens = json_decode(@file_get_contents($store), true) ?: [];
    $token = bin2hex(random_bytes(16));
    $tokens[$token] = [ 'userId' => $userId, 'expires' => time() + $ttl ];
    @file_put_contents($store, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $token;
}

function validate_and_consume_password_reset_token($token) {
    $store = __DIR__ . '/../../data/password_reset_tokens.json';
    if (!file_exists($store)) return false;
    $tokens = json_decode(@file_get_contents($store), true) ?: [];
    if (!isset($tokens[$token])) return false;
    $entry = $tokens[$token];
    if ($entry['expires'] < time()) {
        unset($tokens[$token]);
        @file_put_contents($store, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return false;
    }
    $userId = $entry['userId'];
    // consume
    unset($tokens[$token]);
    @file_put_contents($store, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $userId;
}
