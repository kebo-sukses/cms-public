<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/actions_helper.php';
require_auth();

if (!user_has_permission('manage_templates') && !user_has_permission('manage_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission required: manage_templates or manage_settings']);
    exit;
}

$settings = [];
if (file_exists(__DIR__ . '/../data/settings.json')) {
    $settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
}

$repo = getenv('GITHUB_REPO') ?: ($settings['artifactDelivery']['repo'] ?? null);
$workflow = $_POST['workflow'] ?? ($_GET['workflow'] ?? 'deploy-to-cpanel.yml');
$ref = $_POST['ref'] ?? ($_GET['ref'] ?? 'main');

// token precedence: explicit POST token > env var GITHUB_ACTIONS_TRIGGER_TOKEN > artifactDelivery.tokenEnvVar env
$token = $_POST['token'] ?? null;
if (!$token) {
    $token = getenv('GITHUB_ACTIONS_TRIGGER_TOKEN') ?: null;
}
if (!$token && !empty($settings['artifactDelivery']['tokenEnvVar'])) {
    $token = getenv($settings['artifactDelivery']['tokenEnvVar']);
}
if (!$token && !empty($settings['artifactDelivery']['token'])) {
    // last-resort: token stored in settings (discouraged)
    $token = $settings['artifactDelivery']['token'];
}

$res = trigger_github_workflow($repo, $workflow, $ref, $token);
if (!empty($res['ok'])) {
    if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'deploy_triggered', 'repo' => $repo, 'workflow' => $workflow, 'user' => calius_get_current_user()['username'] ?? null]);
    echo json_encode(['success' => true, 'message' => 'Workflow dispatched', 'detail' => $res]);
    exit;
} else {
    if (function_exists('write_audit_entry')) write_audit_entry(['event' => 'deploy_trigger_failed', 'repo' => $repo, 'workflow' => $workflow, 'detail' => $res]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Dispatch failed', 'detail' => $res]);
    exit;
}

?>
