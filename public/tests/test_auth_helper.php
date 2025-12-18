<?php
require_once __DIR__ . '/../admin/api/_auth.php';

echo "Running auth helper tests...\n";

// Simulate session
$_SESSION = ['calius_admin' => true, 'calius_user' => ['username' => 'admin', 'id' => 'user-001']];

$user = calius_get_current_user();
if ($user && isset($user['username'])) {
    echo "get_current_user: PASS ({$user['username']})\n";
} else {
    echo "get_current_user: FAIL\n";
}

if (user_has_permission('manage_settings')) {
    echo "user_has_permission(manage_settings): PASS\n";
} else {
    echo "user_has_permission(manage_settings): FAIL\n";
}

echo "Auth helper tests done.\n";

?>
