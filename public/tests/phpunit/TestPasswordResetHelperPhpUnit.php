<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/password_reset_helper.php';

class TestPasswordResetHelperPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testCreateAndConsumeToken() {
        $tmpUser = 'user-test-123';
        $token = create_password_reset_token($tmpUser, 2);
        $this->assertIsString($token);

        $userId = validate_and_consume_password_reset_token($token);
        $this->assertEquals($tmpUser, $userId);

        // token should be consumed
        $this->assertFalse(validate_and_consume_password_reset_token($token));
    }
}
