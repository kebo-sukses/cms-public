<?php
use PHPUnit\Framework\TestCase;

class TestPruneArtifactsPhpUnit extends TestCase {
    public function testPruneDryRunAndApply() {
        $metaPath = __DIR__ . '/../../data/templates_artifacts.json';
        $artDir = __DIR__ . '/../../data/artifacts/templates/';
        @mkdir($artDir, 0755, true);

        // prepare fake artifacts
        $now = time();
        $entries = [];
        for ($i=0;$i<3;$i++) {
            $fname = "test_{$i}_" . uniqid() . '.zip';
            $fpath = $artDir . $fname;
            file_put_contents($fpath, 'dummy');
            $entries[] = [
                'id' => 't' . $i,
                'filename' => $fname,
                'original_name' => 'test-template.zip',
                'uploaded_at' => gmdate('c', $now - ($i * 86400 * 40)), // spaced 40 days
                'size' => filesize($fpath),
                'checksum' => hash_file('sha256', $fpath)
            ];
        }
        file_put_contents($metaPath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Dry run should report removals but not delete
        exec(PHP_BINARY . ' ' . __DIR__ . '/../../scripts/prune-artifacts.php --days=30 --max=10 --dry-run', $out, $rc);
        $this->assertEquals(0, $rc);
        $this->assertFileExists($artDir . $entries[0]['filename']);

        // Apply prune
        exec(PHP_BINARY . ' ' . __DIR__ . '/../../scripts/prune-artifacts.php --days=30 --max=1', $out2, $rc2);
        $this->assertEquals(0, $rc2);

        // Only one should remain per max=1
        $remaining = json_decode(@file_get_contents($metaPath), true) ?: [];
        $this->assertCount(1, $remaining);

        // cleanup
        foreach (glob($artDir . 'test_*') as $f) @unlink($f);
        @unlink($metaPath);
    }
}
