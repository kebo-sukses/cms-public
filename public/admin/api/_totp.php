<?php
// Minimal TOTP helper functions (RFC 6238 compatible)
function base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $b32 = preg_replace('/[^A-Z2-7]/', '', $b32);
    $l = strlen($b32);
    $n = 0;
    $j = 0;
    $binary = '';
    for ($i = 0; $i < $l; $i++) {
        $n = $n << 5;
        $n = $n + strpos($alphabet, $b32[$i]);
        $j += 5;
        if ($j >= 8) {
            $j -= 8;
            $binary .= chr(($n & (0xFF << $j)) >> $j);
        }
    }
    return $binary;
}

function hotp($secret, $counter) {
    $key = base32_decode($secret);
    $counterBytes = pack('NN', ($counter & 0xffffffff00000000) >> 32, $counter & 0xffffffff);
    // Ensure 8 bytes (pack 'J' not portable), do manual
    $counterBytes = pack('NN', 0, $counter);
    $hash = hash_hmac('sha1', $counterBytes, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $code = (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset+1]) & 0xff) << 16 |
            (ord($hash[$offset+2]) & 0xff) << 8 |
            (ord($hash[$offset+3]) & 0xff);
    $otp = $code % 1000000;
    return str_pad($otp, 6, '0', STR_PAD_LEFT);
}

function verify_totp($secret, $token, $timestep = 30, $window = 1) {
    if (!preg_match('/^\d{6}$/', $token)) return false;
    $time = floor(time() / $timestep);
    for ($i = -$window; $i <= $window; $i++) {
        if (hotp($secret, $time + $i) === $token) return true;
    }
    return false;
}

?>
