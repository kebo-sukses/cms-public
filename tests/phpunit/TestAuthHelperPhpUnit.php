<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/_auth.php';

class TestAuthHelperPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testGetCurrentUserAndPermission() {
        // Simulate session
        $_SESSION = ['calius_admin' => true, 'calius_user' => ['username' => 'admin', 'id' => 'user-001']];
        $user = get_current_user();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user['username']);
        $this->assertTrue(user_has_permission('manage_settings'));
    }
}
