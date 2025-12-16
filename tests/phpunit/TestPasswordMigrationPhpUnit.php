<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/_auth.php';

class TestPasswordMigrationPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testLegacySha256IsMigrated() {
        $tmpdir = sys_get_temp_dir() . '/calius_pwd_' . uniqid();
        mkdir($tmpdir);
        $usersPath = $tmpdir . '/users.json';
        $password = 'UnitTestPass123!';
        $sha = hash('sha256', $password);
        $user = [ 'id' => 'u1', 'username' => 'tester', 'password' => $sha ];
        file_put_contents($usersPath, json_encode(['users' => [$user]], JSON_PRETTY_PRINT));

        $data = json_decode(file_get_contents($usersPath), true);
        $u = &$data['users'][0];
        $this->assertTrue(verify_password_and_migrate($u, $password, $usersPath));

        $updated = json_decode(file_get_contents($usersPath), true);
        $this->assertStringStartsWith('$', $updated['users'][0]['password']);
        $this->assertArrayHasKey('mustResetPassword', $updated['users'][0]);
        $this->assertTrue($updated['users'][0]['mustResetPassword']);

        // cleanup
        unlink($usersPath); rmdir($tmpdir);
    }

    public function testPasswordVerifyForHashed() {
        $hash = password_hash('abc123', PASSWORD_DEFAULT);
        $user = ['id' => 'u2', 'username' => 'h', 'password' => $hash];
        $this->assertTrue(verify_password_and_migrate($user, 'abc123'));
    }
}
