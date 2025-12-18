<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/_totp.php';

class TestTotpPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testHotpAndTotp() {
        $secret = 'JBSWY3DPEHPK3PXP';
        $counter = floor(time() / 30);
        $token = hotp($secret, $counter);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $token);
        $this->assertTrue(verify_totp($secret, $token));
    }
}
