<?php
use PHPUnit\Framework\TestCase;

class TestUploadConfigPhpUnit extends TestCase {
    public function testUploadSettingsExist() {
        $settings = json_decode(file_get_contents(__DIR__ . '/../../data/settings.json'), true);
        $this->assertArrayHasKey('security', $settings);
        $this->assertArrayHasKey('uploadQuotaPerDay', $settings['security']);
        $this->assertIsInt(intval($settings['security']['uploadQuotaPerDay']));
        $this->assertArrayHasKey('virusScannerCmd', $settings['security']);
    }
}
