<?php
// Integration tests for admin APIs. Skipped by default — enable with environment variable ENABLE_INTEGRATION=1

class TestApiIntegrationPhpUnit extends \PHPUnit\Framework\TestCase {
    public static $serverPid = null;
    public static $tmpDataDir = null;
    public static $serverUrl = null;

    public static function setUpBeforeClass(): void {
        if (getenv('ENABLE_INTEGRATION') !== '1') {
            self::markTestSkipped('Integration tests are disabled. Set ENABLE_INTEGRATION=1 to enable.');
        }

        $docroot = realpath(__DIR__ . '/../../');

        // Create an isolated temp data dir for the server to avoid clobbering repo data
        $tmpData = sys_get_temp_dir() . '/calius_integration_' . uniqid();
        if (!mkdir($tmpData) && !is_dir($tmpData)) {
            throw new \RuntimeException('Failed to create temp data dir');
        }

        // Choose an available port by binding to 0 and reading the assigned port
        $sock = @stream_socket_server('tcp://127.0.0.1:0');
        if (! $sock) {
            throw new Exception('Failed to find a free port for integration server');
        }
        $name = stream_socket_get_name($sock, false);
        fclose($sock);
        $parts = explode(':', $name);
        $port = (int) array_pop($parts);

        // Start server with the temp data dir exported so child server inherits it
        $cmd = sprintf('CMS_DATA_DIR=%s php -S 127.0.0.1:%d -t %s >/tmp/php-server.log 2>&1 & echo $!', escapeshellarg($tmpData), $port, escapeshellarg($docroot));
        $pid = (int) shell_exec($cmd);
        if ($pid <= 0) {
            throw new Exception('Failed to start built-in PHP server');
        }

        // Wait for server readiness (poll)
        $max = 30; $ok = false; $url = sprintf('http://127.0.0.1:%d/admin/api/whoami.php', $port);
        for ($i = 0; $i < $max; $i++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!empty($info['http_code']) || $info['http_code'] === 0) {
                $ok = true; break;
            }
            usleep(200000);
        }
        if (! $ok) {
            // capture server log for diagnostics
            $log = @file_get_contents('/tmp/php-server.log');
            throw new Exception('Server did not become ready: ' . substr((string)$log, 0, 2000));
        }

        // store state
        self::$serverPid = $pid;
        putenv('CMS_DATA_DIR=' . $tmpData);
        self::$tmpDataDir = $tmpData;
        self::$serverUrl = sprintf('http://127.0.0.1:%d', $port);
    }

    public static function tearDownAfterClass(): void {
        if (self::$serverPid) {
            // best-effort kill (works on Linux runners)
            @exec('kill ' . (int) self::$serverPid);
            self::$serverPid = null;
        }
        if (!empty(self::$tmpDataDir) && is_dir(self::$tmpDataDir)) {
            // remove temp files created during test
            @array_map('unlink', glob(self::$tmpDataDir . '/*'));
            @rmdir(self::$tmpDataDir);
        }
    }

    public function testWhoamiWithoutAuth() {
        $ch = curl_init(self::$serverUrl . '/admin/api/whoami.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // Expect non-200 or empty body since no session
        $this->assertNotEquals(200, $info['http_code']);
    }

    public function testPasswordResetFlow() {
        // create a legacy user in the temp data dir
        $users = [ 'users' => [ [ 'id' => 'u1', 'username' => 'testuser', 'email' => 'test@example.com', 'password' => hash('sha256', 'oldpass'), 'role' => 'admin' ] ] ];
        file_put_contents(self::$tmpDataDir . '/users.json', json_encode($users, JSON_PRETTY_PRINT));

        // login - should receive requiresPasswordChange + token
        $ch = curl_init(self::$serverUrl . '/admin/api/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'testuser', 'password' => 'oldpass']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $out = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $json = json_decode($out, true);
        $this->assertTrue(isset($json['requiresPasswordChange']) && !empty($json['token']));

        $token = $json['token'];

        // complete password reset using token
        $cookieFile = tempnam(sys_get_temp_dir(), 'calius_cookie');
        $ch = curl_init(self::$serverUrl . '/admin/api/complete-password-reset.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['token' => $token, 'newPassword' => 'NewPass123!']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        $out = curl_exec($ch);
        $json = json_decode($out, true);
        curl_close($ch);
        $this->assertTrue(!empty($json['success']) && !empty($json['csrfToken']));

        // whoami should work using the session cookies
        $ch = curl_init(self::$serverUrl . '/admin/api/whoami.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        $out = curl_exec($ch);
        $json = json_decode($out, true);
        curl_close($ch);
        $this->assertTrue(!empty($json['success']) && !empty($json['user']) && $json['user']['username'] === 'testuser');

        // login with new password should succeed
        $ch = curl_init(self::$serverUrl . '/admin/api/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'testuser', 'password' => 'NewPass123!']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $out = curl_exec($ch);
        $json = json_decode($out, true);
        $this->assertTrue(!empty($json['success']) && !empty($json['csrfToken']));

        $csrf = $json['csrfToken'];

        // Attempt to save without CSRF header - should fail
        $ch = curl_init(self::$serverUrl . '/admin/api/save-json.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['file' => 'settings', 'data' => ['site' => ['name' => 'x']]]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        $out = curl_exec($ch);
        $json2 = json_decode($out, true);
        curl_close($ch);
        $this->assertTrue(!$json2 || !empty($json2['success']) === false || (isset($json2['message']) && stripos($json2['message'], 'CSRF') !== false));

        // Attempt to save with CSRF header - should succeed
        $ch = curl_init(self::$serverUrl . '/admin/api/save-json.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['file' => 'settings', 'data' => ['site' => ['name' => 'Unit Test']]]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-CSRF-Token: ' . $csrf]);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        $out = curl_exec($ch);
        $json3 = json_decode($out, true);
        curl_close($ch);
        $this->assertTrue(!empty($json3['success']));
    }

    public function testEmergencyPasswordResetRequest() {
        // enable integration tests requirement
        // create settings with emergency key in temp data dir
        $settings = [ 'security' => [ 'emergencyResetKey' => 'sekrit-key', 'emergencyResetLimitPerHour' => 5 ] ];
        file_put_contents(self::$tmpDataDir . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT));

        // create a user
        $users = [ 'users' => [ [ 'id' => 'u2', 'username' => 'emuser', 'email' => 'em@example.com', 'password' => password_hash('P@ssw0rd', PASSWORD_DEFAULT), 'role' => 'admin' ] ] ];
        file_put_contents(self::$tmpDataDir . '/users.json', json_encode($users, JSON_PRETTY_PRINT));

        // request emergency token
        $ch = curl_init(self::$serverUrl . '/admin/api/request-password-reset.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'emuser', 'emergencyKey' => 'sekrit-key']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $out = curl_exec($ch);
        $json = json_decode($out, true);
        curl_close($ch);

        $this->assertTrue(!empty($json['success']) && !empty($json['token']));

        // consume token via complete-password-reset
        $ch = curl_init(self::$serverUrl . '/admin/api/complete-password-reset.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['token' => $json['token'], 'newPassword' => 'FreshPass123!']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $out2 = curl_exec($ch);
        $json2 = json_decode($out2, true);
        curl_close($ch);

        $this->assertTrue(!empty($json2['success']) && !empty($json2['csrfToken']));
    }
}
