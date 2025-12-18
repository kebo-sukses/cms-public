<?php
require_once __DIR__ . '/../admin/api/_totp.php';

echo "Running TOTP tests...\n";

$secret = 'JBSWY3DPEHPK3PXP'; // base32 for 'Hello!'
$counter = floor(time()/30);
$token = hotp($secret, $counter);
echo "Generated token: $token\n";

if (verify_totp($secret, $token)) {
    echo "TOTP verify: PASS\n";
} else {
    echo "TOTP verify: FAIL\n";
}

?>
