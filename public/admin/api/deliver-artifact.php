<?php
require_once __DIR__ . '/_auth.php';
require_auth();

// require manage_templates permission
// note: user_has_permission uses calius_get_current_user internally
if (!user_has_permission('manage_templates')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission required: manage_templates']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }

$metaPath = __DIR__ . '/../data/templates_artifacts.json';
if (!file_exists($metaPath)) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'No artifacts metadata']); exit; }
$meta = json_decode(@file_get_contents($metaPath), true) ?: [];

$found = null; foreach ($meta as $i => $m) { if (($m['id'] ?? '') === $id) { $found = &$meta[$i]; break; } }
if (!$found) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Artifact not found']); exit; }

$artifactsDir = __DIR__ . '/../data/artifacts/templates/';
$path = $artifactsDir . ($found['filename'] ?? '');
if (!file_exists($path)) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Artifact file missing']); exit; }

if (!file_exists(__DIR__ . '/github_helper.php')) {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Delivery helper missing']); exit;
}

require_once __DIR__ . '/github_helper.php';
$res = deliver_artifact_to_github($path, $found['filename'] ?? '', $found);
if (!empty($res['ok'])) {
    $found['delivered'] = true;
    $found['deliver_info'] = $res;
    @file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'artifact_delivered', 'file' => $found['filename'], 'user' => calius_get_current_user()['username'] ?? null]);
    echo json_encode(['success' => true, 'message' => 'Delivered', 'info' => $res]);
    exit;
} else {
    if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'artifact_delivery_failed', 'file' => $found['filename'], 'detail' => $res]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Delivery failed', 'detail' => $res]);
    exit;
}

?>
