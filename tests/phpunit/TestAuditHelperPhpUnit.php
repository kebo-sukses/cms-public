<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/audit_helper.php';

class TestAuditHelperPhpUnit extends \PHPUnit\Framework\TestCase {
    public function testWriteAndReadAudit() {
        $tmpdir = sys_get_temp_dir() . '/calius_audit_' . uniqid();
        mkdir($tmpdir);
        putenv('CMS_DATA_DIR=' . $tmpdir);

        $entry = ['user' => 'tester', 'file' => 'settings', 'action' => 'update', 'summary' => 'test'];
        $this->assertTrue(write_audit_entry($entry));

        $entries = read_audit_entries(['file' => 'settings']);
        $this->assertNotEmpty($entries);
        $this->assertEquals('tester', $entries[count($entries)-1]['user']);

        // cleanup
        unlink($tmpdir . '/audit.json'); rmdir($tmpdir);
    }
}
