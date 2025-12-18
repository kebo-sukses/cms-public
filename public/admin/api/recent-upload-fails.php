<?php
require_once __DIR__ . '/_auth.php';
require_auth();

// Permission: view_analytics
if (!user_has_permission('view_analytics')) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
}

require_once __DIR__ . '/audit_helper.php';
$all = read_audit_entries();
$fails = array_values(array_filter($all, function($e){
    return isset($e['event']) && ($e['event'] === 'upload_failed' || strpos($e['event'],'delivery_failed')!==false);
}));
// return most recent first, limit 25
$fails = array_reverse($fails);
$fails = array_slice($fails, 0, 25);

echo json_encode(['success' => true, 'count' => count($fails), 'entries' => $fails]);

?>
