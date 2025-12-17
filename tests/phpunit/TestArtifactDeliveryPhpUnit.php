<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

final class TestArtifactDeliveryPhpUnit extends TestCase {
    public function test_delivery_missing_token_does_not_block_save() {
        // temporarily set settings to enable delivery with a non-existent env var
        $settingsPath = __DIR__ . '/../../admin/data/settings.json';
        $siteSettingsPath = __DIR__ . '/../../data/settings.json';
        $orig = @file_get_contents($siteSettingsPath);
        $s = json_decode($orig, true) ?: [];
        $s['artifactDelivery'] = [
            'enabled' => true,
            'method' => 'github_release',
            'repo' => 'owner/repo',
            'tokenEnvVar' => 'NONEXISTENT_TOKEN'
        ];
        file_put_contents($siteSettingsPath, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // create a dummy zip file
        $tmp = sys_get_temp_dir() . '/test-artifact-' . time() . '.zip';
        file_put_contents($tmp, 'dummy');

        require_once __DIR__ . '/../../admin/api/upload_helpers.php';
        $res = save_template_artifact($tmp, 'foo.zip', 'unittest', 'v0.1');

        $this->assertIsArray($res);
        $this->assertArrayHasKey('delivered', $res);
        $this->assertFalse($res['delivered']);
        $this->assertArrayHasKey('deliver_info', $res);
        $this->assertStringContainsString('missing_token', json_encode($res['deliver_info']));

        // cleanup: remove file and restore settings
        @unlink(__DIR__ . '/../../data/artifacts/templates/' . ($res['filename'] ?? ''));
        file_put_contents($siteSettingsPath, $orig);
    }
}
