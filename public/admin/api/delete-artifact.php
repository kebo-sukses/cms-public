<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/_auth.php';

require_auth();
if (!user_has_permission('manage_templates')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }

$metaPath = __DIR__ . '/../data/templates_artifacts.json';
$entries = [];
if (file_exists($metaPath)) $entries = json_decode(@file_get_contents($metaPath), true) ?: [];

$found = null; $idx = null;
foreach ($entries as $i => $e) { if (($e['id'] ?? null) === $id) { $found = $e; $idx = $i; break; } }
if (!$found) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Not found']); exit; }

$file = __DIR__ . '/../data/artifacts/templates/' . $found['filename'];
if (file_exists($file)) { @unlink($file); }

// remove metadata entry
array_splice($entries, $idx, 1);
@file_put_contents($metaPath, json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if (function_exists('write_audit_entry')) write_audit_entry(['user' => $_SESSION['calius_user']['username'] ?? ($_SESSION['calius_admin'] ? 'admin' : null), 'file' => 'templates_artifacts', 'action' => 'delete', 'summary' => $found['original_name']]);

echo json_encode(['success' => true, 'message' => 'Deleted']);
exit;

?>
