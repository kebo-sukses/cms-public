<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/_auth.php';

require_once __DIR__ . '/save_helper.php';
require_once __DIR__ . '/audit_helper.php';
require_once __DIR__ . '/csrf_helper.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$file = $input['file'] ?? '';
$data = $input['data'] ?? null;

if ($data === null) json_fail('Missing data', 400);

require_auth();

// CSRF protection
require_csrf();
$currentUser = calius_get_current_user();

try {
    save_json_file($file, $data, $currentUser);
    // Create an audit entry summarizing the change
    $oldPath = __DIR__ . '/../../data/' . $file . '.json';
    $old = file_exists($oldPath) ? json_decode(@file_get_contents($oldPath), true) : null;
    $changedKeys = [];
    if (is_array($old) && is_array($data)) {
        $keys = array_unique(array_merge(array_keys($old), array_keys($data)));
        foreach ($keys as $k) {
            $oldVal = json_encode($old[$k] ?? null);
            $newVal = json_encode($data[$k] ?? null);
            if ($oldVal !== $newVal) $changedKeys[] = $k;
        }
    }
    $summary = 'Updated ' . $file . '. Changed keys: ' . implode(', ', $changedKeys);
    write_audit_entry([
        'user' => $currentUser['username'] ?? ($currentUser['email'] ?? 'unknown'),
        'userId' => $currentUser['id'] ?? null,
        'file' => $file,
        'action' => 'update',
        'summary' => $summary,
        'changedKeys' => $changedKeys
    ]);
    echo json_encode(['success' => true, 'message' => 'Saved ' . $file]);
} catch (Exception $e) {
    json_fail($e->getMessage(), 400);
}
?>
