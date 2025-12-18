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

$metaPath = __DIR__ . '/../data/templates_artifacts.json';
$entries = [];
if (file_exists($metaPath)) {
    $entries = json_decode(@file_get_contents($metaPath), true) ?: [];
}

// optional filtering by id
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    foreach ($entries as $e) {
        if ($e['id'] === $id) { echo json_encode(['success' => true, 'entry' => $e]); exit; }
    }
    http_response_code(404); echo json_encode(['success' => false, 'message' => 'Not found']); exit;
}

echo json_encode(['success' => true, 'total' => count($entries), 'entries' => array_values(array_reverse($entries))]);

?>
