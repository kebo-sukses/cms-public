<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/upload_helpers.php';

class TestTemplateArtifactPhpUnit extends TestCase {
    public function testSaveTemplateArtifactCreatesMetadata() {
        $tmpDir = sys_get_temp_dir();
        $tmpZip = $tmpDir . '/test_template_' . uniqid() . '.zip';

        $zip = new ZipArchive();
        $res = $zip->open($tmpZip, ZipArchive::CREATE);
        $this->assertTrue($res === true);
        $zip->addFromString('index.html', '<html><body>test</body></html>');
        $zip->close();

        $metaBefore = [];
        $metaPath = __DIR__ . '/../../data/templates_artifacts.json';
        if (file_exists($metaPath)) $metaBefore = json_decode(@file_get_contents($metaPath), true) ?: [];

        $result = save_template_artifact($tmpZip, 'test-template.zip', 'unittest');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('checksum', $result);

        // Verify file exists
        $filePath = __DIR__ . '/../../data/artifacts/templates/' . $result['filename'];
        $this->assertFileExists($filePath);
        $this->assertEquals(hash_file('sha256', $filePath), $result['checksum']);

        // Verify metadata was appended
        $meta = json_decode(@file_get_contents($metaPath), true) ?: [];
        $this->assertNotEquals(count($metaBefore), count($meta));

        // cleanup
        @unlink($filePath);
        // remove last entry
        if (file_exists($metaPath)) {
            $m = json_decode(@file_get_contents($metaPath), true) ?: [];
            $m = array_filter($m, function($e) use ($result) { return ($e['id'] ?? '') !== $result['id']; });
            @file_put_contents($metaPath, json_encode(array_values($m), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
