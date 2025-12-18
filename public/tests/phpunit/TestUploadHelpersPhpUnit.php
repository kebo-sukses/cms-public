<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/upload_helpers.php';

class TestUploadHelpersPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testRunVirusScanMock() {
        // Create dummy file
        $tmp = sys_get_temp_dir() . '/test-upload.txt';
        file_put_contents($tmp, 'hello');

        // Temporarily set settings to point to a script that returns non-zero
        $settingsPath = __DIR__ . '/../../data/settings.json';
        $orig = json_decode(file_get_contents($settingsPath), true);
        $origSec = $orig['security'];

        // create a fake scanner script that returns 0
        $okScript = __DIR__ . '/../bin/scan_ok.sh';
        @mkdir(dirname($okScript), 0755, true);
        file_put_contents($okScript, "#!/bin/sh\nexit 0\n");
        chmod($okScript, 0755);

        $orig['security']['virusScannerCmd'] = $okScript;
        file_put_contents($settingsPath, json_encode($orig, JSON_PRETTY_PRINT));

        $this->assertTrue(run_virus_scan($tmp));

        // cleanup & restore
        unlink($okScript);
        file_put_contents($settingsPath, json_encode(array_merge($orig, ['security' => $origSec]), JSON_PRETTY_PRINT));
        unlink($tmp);
    }

    public function testRunVirusScanFails() {
        $tmp = sys_get_temp_dir() . '/test-upload.txt';
        file_put_contents($tmp, 'bad');

        $settingsPath = __DIR__ . '/../../data/settings.json';
        $orig = json_decode(file_get_contents($settingsPath), true);
        $origSec = $orig['security'];

        $badScript = __DIR__ . '/../bin/scan_bad.sh';
        @mkdir(dirname($badScript), 0755, true);
        file_put_contents($badScript, "#!/bin/sh\nexit 2\n");
        chmod($badScript, 0755);

        $orig['security']['virusScannerCmd'] = $badScript;
        file_put_contents($settingsPath, json_encode($orig, JSON_PRETTY_PRINT));

        $this->assertFalse(run_virus_scan($tmp));

        // cleanup
        unlink($badScript);
        file_put_contents($settingsPath, json_encode(array_merge($orig, ['security' => $origSec]), JSON_PRETTY_PRINT));
        unlink($tmp);
    }

    public function testRunVirusScanClamscanIfAvailable() {
        // If clamscan exists on PATH, run it against a small file (should return 0 for clean files)
        $which = trim(shell_exec('command -v clamscan 2>/dev/null'));
        if (!$which) {
            $this->markTestSkipped('clamscan not installed in environment');
        }
        $tmp = sys_get_temp_dir() . '/test-upload.txt';
        file_put_contents($tmp, 'clean');
        $this->assertTrue(run_virus_scan($tmp));
        unlink($tmp);
    }
}
