<?php
session_start();
require_once __DIR__ . '/_auth.php';

require_auth();
if (!user_has_permission('manage_templates')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) { http_response_code(400); echo 'Missing id'; exit; }

$metaPath = __DIR__ . '/../data/templates_artifacts.json';
$entries = [];
if (file_exists($metaPath)) $entries = json_decode(@file_get_contents($metaPath), true) ?: [];

$found = null;
foreach ($entries as $e) if ($e['id'] === $id) { $found = $e; break; }
if (!$found) { http_response_code(404); echo 'Not found'; exit; }

$file = __DIR__ . '/../data/artifacts/templates/' . $found['filename'];
if (!file_exists($file)) { http_response_code(404); echo 'File not found'; exit; }

// send file as attachment
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($found['original_name']) . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;

?>
