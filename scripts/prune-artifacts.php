#!/usr/bin/env php
<?php
// prune-artifacts.php
// Usage: php scripts/prune-artifacts.php [--days=30] [--max=10] [--dry-run]

$opts = getopt('', ['days::', 'max::', 'dry-run']);
$days = isset($opts['days']) ? intval($opts['days']) : null;
$max = isset($opts['max']) ? intval($opts['max']) : null;
$dry = isset($opts['dry-run']);

$settingsPath = __DIR__ . '/../data/settings.json';
$settings = [];
if (file_exists($settingsPath)) $settings = json_decode(@file_get_contents($settingsPath), true) ?: [];
if ($days === null) $days = intval($settings['security']['artifactRetentionDays'] ?? 30);
if ($max === null) $max = intval($settings['security']['maxArtifactVersionsPerTemplate'] ?? 10);

$metaPath = __DIR__ . '/../data/templates_artifacts.json';
$entries = [];
if (file_exists($metaPath)) $entries = json_decode(@file_get_contents($metaPath), true) ?: [];

if (empty($entries)) { echo "No artifacts found.\n"; exit(0); }

// group by original_name
$groups = [];
foreach ($entries as $e) {
    $key = $e['original_name'] ?? $e['filename'];
    $groups[$key][] = $e;
}

$now = time();
$toDelete = [];

foreach ($groups as $key => $list) {
    // sort by uploaded_at desc
    usort($list, function($a, $b) { return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']); });
    // delete older than days
    foreach ($list as $i => $item) {
        $ageDays = ($now - strtotime($item['uploaded_at'])) / 86400;
        if ($ageDays > $days) { $toDelete[$item['id']] = $item; continue; }
        // enforce max versions
        if ($i >= $max) { $toDelete[$item['id']] = $item; continue; }
    }
}

if (empty($toDelete)) { echo "Nothing to prune.\n"; exit(0); }

echo ($dry ? "DRY RUN: " : "Applying: ") . count($toDelete) . " artifacts will be removed.\n";
foreach ($toDelete as $id => $entry) {
    echo " - {$entry['original_name']} ({$entry['filename']}) uploaded {$entry['uploaded_at']}\n";
}

if ($dry) exit(0);

// perform deletions and update meta
$remaining = array_filter($entries, function($e) use ($toDelete) { return !isset($toDelete[$e['id']]); });

// delete files
foreach ($toDelete as $id => $entry) {
    $file = __DIR__ . '/../data/artifacts/templates/' . $entry['filename'];
    if (file_exists($file)) @unlink($file);
    // audit
    require_once __DIR__ . '/../admin/api/audit_helper.php';
    write_audit_entry(['user' => 'system', 'file' => 'templates_artifacts', 'action' => 'prune', 'summary' => $entry['original_name']]);
}

@file_put_contents($metaPath, json_encode(array_values($remaining), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Prune complete. Removed " . count($toDelete) . " artifacts.\n";
exit(0);

?>
