<?php
function run_virus_scan($filepath) {
    $settingsPath = __DIR__ . '/../data/settings.json';
    $settings = [];
    if (file_exists($settingsPath)) {
        $settings = json_decode(@file_get_contents($settingsPath), true) ?: [];
    }
    $scanner = trim($settings['security']['virusScannerCmd'] ?? '');

    // If no scanner configured, try to use clamscan if available on PATH
    if (!$scanner) {
        $which = null;
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows: assume clamscan may be in PATH
            $which = trim(shell_exec('where clamscan 2>NUL'));
        } else {
            $which = trim(shell_exec('command -v clamscan 2>/dev/null'));
        }
        if ($which) {
            $scanner = escapeshellcmd($which) . ' --no-summary --infected';
        } else {
            // No scanner available, allow by default
            return true;
        }
    }

    // Run the scanner and return true only if it exits with 0
    $cmd = $scanner . ' ' . escapeshellarg($filepath) . ' 2>&1';
    exec($cmd, $out, $rc);
    return $rc === 0;
}

function check_upload_quota($userId) {
    $quotaStore = __DIR__ . '/../data/uploads_quota.json';
    $usersettings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
    $quotaLimit = intval($usersettings['security']['uploadQuotaPerDay'] ?? 100);
    $now = time();
    $today = date('Y-m-d');
    $q = [];
    if (file_exists($quotaStore)) $q = json_decode(@file_get_contents($quotaStore), true) ?: [];
    $q[$userId] = $q[$userId] ?? [];
    $q[$userId] = array_filter($q[$userId], function($ts) use ($today) { return date('Y-m-d', $ts) === $today; });
    if (count($q[$userId]) >= $quotaLimit) return false;
    $q[$userId][] = $now;
    @file_put_contents($quotaStore, json_encode($q));
    return true;
}

/**
 * Save uploaded template artifact to a non-web directory, compute checksum,
 * write metadata entry and audit log. Returns metadata on success or false.
 */
function save_template_artifact($tmpPath, $originalName, $uploader = null, $version = null) {
    $artifactsDir = __DIR__ . '/../data/artifacts/templates/';
    if (!is_dir($artifactsDir)) {
        if (!mkdir($artifactsDir, 0755, true)) return false;
    }

    // ensure directory is writable
    if (!is_writable($artifactsDir)) return false;

    $origBase = pathinfo($originalName, PATHINFO_FILENAME);
    $san = preg_replace('/[^a-zA-Z0-9-_]/', '-', $origBase);
    $san = preg_replace('/-+/', '-', $san);
    $san = trim($san, '-');
    $filename = $san . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.zip';
    $dest = $artifactsDir . $filename;

    // Move uploaded file into artifacts directory
    if (!@rename($tmpPath, $dest)) {
        // fallback to copy+unlink if rename fails (different mounts)
        if (!@copy($tmpPath, $dest)) return false;
        @unlink($tmpPath);
    }

    $size = filesize($dest);
    $checksum = hash_file('sha256', $dest);

    $metaPath = __DIR__ . '/../data/templates_artifacts.json';
    $meta = [];
    if (file_exists($metaPath)) $meta = json_decode(@file_get_contents($metaPath), true) ?: [];

    $id = 'art_' . bin2hex(random_bytes(6));
    $entry = [
        'id' => $id,
        'filename' => $filename,
        'original_name' => $originalName,
        'version' => $version,
        'checksum' => $checksum,
        'size' => $size,
        'uploaded_by' => $uploader,
        'uploaded_at' => gmdate('c'),
        'status' => 'uploaded',
        'scan_result' => 'clean'
    ];

    $meta[] = $entry;
    @file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Write audit entry if helper exists
    if (function_exists('write_audit_entry')) {
        write_audit_entry(['user' => $uploader, 'file' => 'templates_artifacts', 'action' => 'upload', 'summary' => $entry['original_name']]);
    }

    // Attempt automatic delivery (non-blocking: do not fail the upload if delivery fails)
    try {
        $settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
        $delivery = $settings['artifactDelivery'] ?? [];
        if (!empty($delivery['enabled']) && ($delivery['method'] ?? '') === 'github_release') {
            if (file_exists(__DIR__ . '/github_helper.php')) {
                require_once __DIR__ . '/github_helper.php';
                $res = deliver_artifact_to_github($dest, $filename, $entry);
                if (!empty($res['ok'])) {
                    $entry['delivered'] = true;
                    $entry['deliver_info'] = $res;
                } else {
                    $entry['delivered'] = false;
                    $entry['deliver_info'] = $res;
                    if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'artifact_delivery_failed', 'file' => $filename, 'detail' => $res]);
                }
                // update meta file with delivery status
                foreach ($meta as &$m) {
                    if ($m['id'] === $id) { $m = $entry; break; }
                }
                @file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
    } catch (Exception $e) {
        if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'artifact_delivery_exception', 'file' => $filename, 'detail' => $e->getMessage()]);
    }

    return $entry;
}

?>
