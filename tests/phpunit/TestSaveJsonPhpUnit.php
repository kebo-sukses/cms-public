<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/save_helper.php';

class TestSaveJsonPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testSaveJsonFileCreatesFile() {
        $tmpdir = sys_get_temp_dir() . '/calius_test_' . uniqid();
        mkdir($tmpdir);
        putenv('CMS_DATA_DIR=' . $tmpdir);

        $file = 'settings';
        $data = ['site' => ['name' => 'Unit Test Site']];
        $this->assertTrue(save_json_file($file, $data, ['role' => 'admin']));
        $path = $tmpdir . '/settings.json';
        $this->assertFileExists($path);
        $contents = json_decode(file_get_contents($path), true);
        $this->assertEquals('Unit Test Site', $contents['site']['name']);

        // audit entry should be created
        $auditPath = $tmpdir . '/audit.json';
        $this->assertFileExists($auditPath);
        $aud = json_decode(file_get_contents($auditPath), true);
        $this->assertIsArray($aud);
        $this->assertEquals($file, $aud[count($aud)-1]['file']);

        // cleanup
        unlink($path);
        rmdir($tmpdir);
    }

    public function testSaveJsonPermissionDenied() {
        $tmpdir = sys_get_temp_dir() . '/calius_test_' . uniqid();
        mkdir($tmpdir);
        putenv('CMS_DATA_DIR=' . $tmpdir);

        $file = 'settings';
        $data = ['site' => ['name' => 'Unit Test Site']];
        $this->expectException(Exception::class);
        save_json_file($file, $data, ['role' => 'editor', 'permissions' => ['manage_templates']]);

        rmdir($tmpdir);
    }
}
