<?php
/**
 * CALIUS DIGITAL - File Upload Handler
 * Handles automatic file uploads for Static CMS
 * 
 * Security Features:
 * - File type validation
 * - File size limit
 * - Secure filename sanitization
 * - Directory traversal prevention
 */

// Security: Start session and check authentication
session_start();

// Simple authentication check (you can enhance this)
$isAuthenticated = isset($_SESSION['calius_admin']) && $_SESSION['calius_admin'] === true;

// Allow if authenticated by session OR referer points to admin
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$isFromAdmin = strpos($referer, '/admin/') !== false;

if (!$isAuthenticated && !$isFromAdmin) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// CSRF protection for authenticated requests
require_once __DIR__ . '/api/csrf_helper.php';
if ($isAuthenticated) require_csrf();

// Optional extra check: if an uploadKey is configured in settings, require it
$settingsPath = __DIR__ . '/../data/settings.json';
$configuredUploadKey = '';
if (file_exists($settingsPath)) {
    $settingsJson = @file_get_contents($settingsPath);
    if ($settingsJson !== false) {
        $settingsData = json_decode($settingsJson, true);
        if (isset($settingsData['security']['uploadKey'])) {
            $configuredUploadKey = (string)$settingsData['security']['uploadKey'];
        }
    }
}

if ($configuredUploadKey !== '') {
    $providedKey = '';
    // Accept header or form field
    if (isset($_SERVER['HTTP_X_UPLOAD_KEY'])) {
        $providedKey = $_SERVER['HTTP_X_UPLOAD_KEY'];
    } elseif (isset($_POST['uploadKey'])) {
        $providedKey = $_POST['uploadKey'];
    }

    if ($providedKey !== $configuredUploadKey) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Upload key missing or invalid'
        ]);
        exit;
    }
}

// Configuration
$uploadConfig = [
    'logo' => [
        'path' => __DIR__ . '/../assets/images/',
        'url' => '/assets/images/',
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        'max_size' => 2 * 1024 * 1024, // 2MB
    ],
    'template' => [
        'path' => __DIR__ . '/../templates/',
        'url' => '/templates/',
        'allowed_types' => ['application/zip'],
        'allowed_extensions' => ['zip'],
        'max_size' => 50 * 1024 * 1024, // 50MB
    ],
    'blog-image' => [
        'path' => __DIR__ . '/../assets/images/blog/',
        'url' => '/assets/images/blog/',
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 5 * 1024 * 1024, // 5MB
    ]
];

// Get upload type
$uploadType = isset($_POST['type']) ? $_POST['type'] : 'logo';

// Validate upload type
if (!isset($uploadConfig[$uploadType])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid upload type'
    ]);
    exit;
}

$config = $uploadConfig[$uploadType];

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'No file uploaded';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'File too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'File upload incomplete';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No file selected';
                break;
            default:
                $errorMessage = 'Upload error occurred';
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    exit;
}

$file = $_FILES['file'];

// Validate file size
if ($file['size'] > $config['max_size']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'File too large. Maximum size: ' . ($config['max_size'] / 1024 / 1024) . 'MB'
    ]);
    exit;
}

// Rate limiting: max uploads per IP per minute
$rateStore = __DIR__ . '/../data/uploads_rate.json';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limit = 10; // max uploads
$period = 60; // seconds
$rates = [];
if (file_exists($rateStore)) {
    $rCont = @file_get_contents($rateStore);
    $rates = $rCont ? json_decode($rCont, true) : [];
}
$now = time();
$rates[$ip] = array_filter($rates[$ip] ?? [], function($t) use ($now, $period) { return ($now - $t) < $period; });
if (count($rates[$ip]) >= $limit) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many uploads, slow down']);
    exit;
}
$rates[$ip][] = $now;
@file_put_contents($rateStore, json_encode($rates));

// Per-user quota (daily)
$quotaStore = __DIR__ . '/../data/uploads_quota.json';
$usersettings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
$quotaLimit = intval($usersettings['security']['uploadQuotaPerDay'] ?? 100);
$userId = $_SESSION['calius_user']['id'] ?? null;
if ($userId) {
    $q = [];
    if (file_exists($quotaStore)) {
        $q = json_decode(@file_get_contents($quotaStore), true) ?: [];
    }
    $today = date('Y-m-d');
    $q[$userId] = $q[$userId] ?? [];
    $q[$userId] = array_filter($q[$userId], function($ts) use ($today) { return date('Y-m-d', $ts) === $today; });
    if (count($q[$userId]) >= $quotaLimit) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Upload quota exceeded for today']);
        exit;
    }
    $q[$userId][] = $now;
    @file_put_contents($quotaStore, json_encode($q));
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $config['allowed_types'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Allowed: ' . implode(', ', $config['allowed_extensions'])
    ]);
    exit;
}

// Validate file extension
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExtension, $config['allowed_extensions'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file extension. Allowed: ' . implode(', ', $config['allowed_extensions'])
    ]);
    exit;
}

// Additional validation for zip: ensure no PHP or executable inside archive
if ($fileExtension === 'zip' && $uploadType === 'template') {
    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'phtml', 'exe', 'sh', 'bat'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Archive contains disallowed file types']);
                exit;
            }
        }
        $zip->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid zip archive']);
        exit;
    }
}

require_once __DIR__ . '/api/upload_helpers.php';

// Run virus scan
if (!run_virus_scan($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File failed virus scan']);
    exit;
}

// Sanitize filename
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$sanitizedName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $originalName);
$sanitizedName = preg_replace('/-+/', '-', $sanitizedName);
$sanitizedName = trim($sanitizedName, '-');

// Generate unique filename if file exists
$filename = $sanitizedName . '.' . $fileExtension;
$targetPath = $config['path'] . $filename;
$counter = 1;

while (file_exists($targetPath)) {
    $filename = $sanitizedName . '-' . $counter . '.' . $fileExtension;
    $targetPath = $config['path'] . $filename;
    $counter++;
}

// Create directory if it doesn't exist
if (!is_dir($config['path'])) {
    if (!mkdir($config['path'], 0755, true)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create upload directory'
        ]);
        exit;
    }
}

// Special handling for template artifacts: move into non-web artifacts dir and write metadata
if ($uploadType === 'template') {
    // Use helper to save artifact (moves file, computes checksum, writes metadata)
    $uploader = $_SESSION['calius_user']['username'] ?? ($_SESSION['calius_admin'] ? 'admin' : null);
    $artifact = save_template_artifact($file['tmp_name'], $file['name'], $uploader);
    if (!$artifact) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save artifact']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Template uploaded successfully',
        'artifact' => $artifact
    ]);
    exit;
}

// Move uploaded file for other types
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save file'
    ]);
    exit;
}

// Success response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'File uploaded successfully',
    'data' => [
        'filename' => $filename,
        'path' => $config['url'] . $filename,
        'size' => $file['size'],
        'type' => $mimeType
    ]
]);
?>
