<?php
// Simple test runner - runs multiple test scripts and reports
chdir(__DIR__);
require_once 'test_totp.php';
require_once 'test_auth_helper.php';

echo "All tests executed.\n";

?>
