<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/csrf_helper.php';

class TestCsrfHelperPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testGetAndRequireCsrf() {
        // ensure session available
        if (session_status() === PHP_SESSION_NONE) session_start();
        // get token
        $token = get_csrf_token();
        $this->assertIsString($token);

        // simulate header present
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        // Should not exit; call require_csrf and expect no output
        ob_start();
        require_csrf();
        $out = ob_get_clean();
        $this->assertEquals('', $out);
    }
}
