<?php
// Simple CLI tests for admin API endpoints. Run via: php tests/test_api.php
function post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookiejar.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookiejar.txt');
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $res];
}

$base = 'http://localhost'; // adjust if needed
echo "Testing admin API (ensure local server is running)\n";
// 1) Try saving without login (should fail)
if (file_exists(__DIR__ . '/cookiejar.txt')) unlink(__DIR__ . '/cookiejar.txt');
list($code, $res) = post($base . '/admin/api/save-json.php', ['file' => 'settings', 'data' => ['site' => ['name' => 'x']]]);
echo "Unauthorized save attempt: HTTP $code -- $res\n";

// 2) Login (without 2FA)
list($code, $res) = post($base . '/admin/api/login.php', ['username' => 'admin', 'password' => 'admin123']);
echo "Login: HTTP $code -- $res\n";

// 2a) Wrong password should fail
list($code, $res) = post($base . '/admin/api/login.php', ['username' => 'admin', 'password' => 'wrongpass']);
echo "Login (wrong password): HTTP $code -- $res\n";

// 3) Save settings (should be allowed when logged in)
$settings = json_decode(file_get_contents(__DIR__ . '/../../data/settings.json'), true);
$settings['site']['name'] = 'Calius Digital (Test)';
list($code, $res) = post($base . '/admin/api/save-json.php', ['file' => 'settings', 'data' => $settings]);
echo "Save settings (authenticated): HTTP $code -- $res\n";

// 4) Setup 2FA (generate secret) and verify using server-side TOTP
list($code, $res) = post($base . '/admin/api/setup-2fa.php', []);
echo "Setup 2FA: HTTP $code -- $res\n";
$setup = json_decode($res, true);
if (isset($setup['secret'])) {
    require_once __DIR__ . '/../admin/api/_totp.php';
    $secret = $setup['secret'];
    $counter = floor(time() / 30);
    $token = hotp($secret, $counter);

    list($code, $res) = post($base . '/admin/api/verify-2fa.php', ['secret' => $secret, 'token' => $token]);
    echo "Verify 2FA: HTTP $code -- $res\n";
} else {
    echo "No secret returned from setup-2fa\n";
}

echo "Tests finished.\n";

?>
