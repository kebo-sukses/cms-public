<?php
require_once __DIR__ . '/_auth.php';
require_auth();

$artifactsDir = __DIR__ . '/../data/artifacts/templates/';
$metaPath = __DIR__ . '/../data/templates_artifacts.json';

$info = [
    'artifacts_path' => $artifactsDir,
    'exists' => is_dir($artifactsDir),
    'writable' => is_dir($artifactsDir) ? is_writable($artifactsDir) : null,
    'artifacts_count' => 0,
    'artifacts_size' => 0,
    'meta_exists' => file_exists($metaPath),
    'meta_writable' => file_exists($metaPath) ? is_writable($metaPath) : null,
    'meta_entries' => 0
];

if (is_dir($artifactsDir)) {
    $files = glob($artifactsDir . '*.zip');
    $info['artifacts_count'] = $files ? count($files) : 0;
    $size = 0;
    if ($files) {
        foreach ($files as $f) $size += filesize($f);
    }
    $info['artifacts_size'] = $size;
}

if (file_exists($metaPath)) {
    $meta = json_decode(@file_get_contents($metaPath), true) ?: [];
    $info['meta_entries'] = is_array($meta) ? count($meta) : 0;
}

echo json_encode(['success' => true, 'storage' => $info]);

?>
