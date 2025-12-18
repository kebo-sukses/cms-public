<?php
// Helper functions for saving JSON files in a controlled way
function cms_data_dir() {
    $env = getenv('CMS_DATA_DIR');
    if ($env && is_dir($env)) return rtrim($env, '/');
    return __DIR__ . '/../../data';
}

function save_json_file($file, $data, $currentUser = null) {
    $allowed = ['templates' => 'manage_templates', 'settings' => 'manage_settings', 'blog' => 'manage_blog', 'orders' => 'manage_orders', 'users' => 'manage_users'];
    if (!isset($allowed[$file])) throw new Exception('File type not allowed');

    // permissions: currentUser is array with 'permissions' or 'role'
    if ($currentUser) {
        if (!isset($currentUser['role']) || $currentUser['role'] !== 'admin') {
            $perms = $currentUser['permissions'] ?? [];
            if (!in_array($allowed[$file], $perms)) throw new Exception('Forbidden');
        }
    }

    // Basic schema checks
    switch ($file) {
        case 'templates': if (!is_array($data) && !isset($data['templates'])) throw new Exception('Invalid templates structure'); break;
        case 'settings': if (!is_array($data) || !isset($data['site'])) throw new Exception('Invalid settings structure'); break;
        case 'blog': if (!is_array($data) && !isset($data['posts'])) throw new Exception('Invalid blog structure'); break;
    }

    $dir = cms_data_dir();
    $path = $dir . '/' . $file . '.json';

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) throw new Exception('JSON encode failed');

    // Backup
    if (file_exists($path)) copy($path, $path . '.bak-' . time());

    if (file_put_contents($path, $encoded) === false) throw new Exception('Failed to write file');

    return true;
}

?>
