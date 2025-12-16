<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/audit_helper.php';

require_auth();
// permission: view_analytics
if (!user_has_permission('view_analytics')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$filters = [];
if (isset($_GET['file'])) $filters['file'] = $_GET['file'];
if (isset($_GET['user'])) $filters['user'] = $_GET['user'];
if (isset($_GET['since'])) $filters['since'] = $_GET['since'];
if (isset($_GET['until'])) $filters['until'] = $_GET['until'];

$entries = read_audit_entries($filters);
// support pagination
$limit = min(100, intval($_GET['limit'] ?? 50));
$offset = max(0, intval($_GET['offset'] ?? 0));
$paged = array_slice($entries, $offset, $limit);

echo json_encode(['success' => true, 'total' => count($entries), 'offset' => $offset, 'limit' => $limit, 'entries' => $paged]);

?>
