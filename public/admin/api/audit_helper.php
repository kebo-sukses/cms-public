<?php
// Audit helper: write audit entries into data/audit.json safely
function audit_store_path() {
    $env = getenv('CMS_DATA_DIR');
    if ($env && is_dir($env)) return rtrim($env, '/') . '/audit.json';
    return __DIR__ . '/../../data/audit.json';
}

function write_audit_entry($entry) {
    $path = audit_store_path();
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entries = [];
    if (file_exists($path)) {
        $contents = @file_get_contents($path);
        $entries = $contents ? json_decode($contents, true) : [];
        if (!is_array($entries)) $entries = [];
    }

    // Add timestamp, ip
    $entry['timestamp'] = gmdate('c');
    $entry['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;

    $entries[] = $entry;

    // write with lock
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    rename($tmp, $path);

    return true;
}

function read_audit_entries($filters = []) {
    $path = audit_store_path();
    if (!file_exists($path)) return [];
    $entries = json_decode(@file_get_contents($path), true) ?: [];

    // apply simple filters: file, user, since, until
    return array_values(array_filter($entries, function($e) use ($filters) {
        if (isset($filters['file']) && ($e['file'] ?? '') !== $filters['file']) return false;
        if (isset($filters['user']) && ($e['user'] ?? '') !== $filters['user']) return false;
        if (isset($filters['since']) && strtotime($e['timestamp']) < strtotime($filters['since'])) return false;
        if (isset($filters['until']) && strtotime($e['timestamp']) > strtotime($filters['until'])) return false;
        return true;
    }));
}

?>
