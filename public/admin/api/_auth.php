<?php
// Use secure session cookie settings where possible before starting session
if (php_sapi_name() !== 'cli') {
    $cookieParams = session_get_cookie_params();
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
session_start();

function json_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function calius_get_current_user() {
    if (empty($_SESSION['calius_admin'])) return null;
    $usersPath = __DIR__ . '/../../data/users.json';
    if (!file_exists($usersPath)) return null;
    $data = json_decode(file_get_contents($usersPath), true);
    if (!$data || !isset($data['users']) || !is_array($data['users'])) return null;
    $sessionUser = $_SESSION['calius_user'] ?? null;
    if ($sessionUser) {
        foreach ($data['users'] as $u) {
            if (isset($u['id']) && $u['id'] === $sessionUser['id']) return $u;
            if (isset($u['username']) && $u['username'] === $sessionUser['username']) return $u;
            if (isset($u['email']) && $u['email'] === $sessionUser['username']) return $u;
        }
    }
    // fallback to first user
    return $data['users'][0];
}

function require_auth() {
    if (empty($_SESSION['calius_admin'])) {
        json_fail('Unauthorized', 401);
    }
}

function user_has_permission($perm) {
    $user = get_current_user();
    if (!$user) return false;
    if (!empty($user['role']) && $user['role'] === 'admin') return true;
    $perms = $user['permissions'] ?? [];
    if (!is_array($perms)) $perms = explode(',', (string)$perms);
    return in_array($perm, $perms);
}

// Verify a password for a user and migrate legacy SHA-256 hashes to modern password_hash
function verify_password_and_migrate(array &$user, string $password, string $usersPath = null): bool {
    $ok = false;
    if (isset($user['password'])) {
        if (strpos($user['password'], '$') === 0 || strlen($user['password']) > 60) {
            $ok = password_verify($password, $user['password']);
        } else {
            $ok = hash_equals($user['password'], hash('sha256', $password));
            if ($ok) {
                // migrate to modern hash
                $user['password'] = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
                if ($usersPath) {
                    // attempt to write back the updated users file
                    $data = json_decode(@file_get_contents($usersPath), true) ?: [];
                    if (isset($data['users']) && is_array($data['users'])) {
                        foreach ($data['users'] as &$u) {
                            if ((isset($u['id']) && $u['id'] === $user['id']) || (isset($u['username']) && $u['username'] === $user['username'])) {
                                $u['password'] = $user['password'];
                                break;
                            }
                        }
                        @file_put_contents($usersPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                    // flag the account to require a password reset on next login
                    $user['mustResetPassword'] = true;
                }
            }
        }
    }
    return $ok;
}

?>
