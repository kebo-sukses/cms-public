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

?>
