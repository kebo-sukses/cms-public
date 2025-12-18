<?php
/**
 * Minimal GitHub Release helper for uploading template artifacts.
 * Relies on a token provided via environment variable (name configurable in settings)
 */

function gh_api_request($method, $url, $token, $body = null, $headers = []) {
    $ch = curl_init();
    $defaultHeaders = [
        'User-Agent: CaliusCMS/1.0',
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $token
    ];
    foreach ($headers as $h) $defaultHeaders[] = $h;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [$resp, $info, $err];
}

function gh_get_release_by_tag($ownerRepo, $tag, $token) {
    $url = 'https://api.github.com/repos/' . $ownerRepo . '/releases/tags/' . rawurlencode($tag);
    list($resp, $info, $err) = gh_api_request('GET', $url, $token);
    if ($err) return [false, "curl: $err"];
    $code = $info['http_code'] ?? 0;
    if ($code === 200) return [true, json_decode($resp, true)];
    return [false, $resp];
}

function gh_create_release($ownerRepo, $tag, $name, $token) {
    $url = 'https://api.github.com/repos/' . $ownerRepo . '/releases';
    $body = json_encode(['tag_name' => $tag, 'name' => $name, 'prerelease' => false]);
    list($resp, $info, $err) = gh_api_request('POST', $url, $token, $body);
    if ($err) return [false, "curl: $err"];
    $code = $info['http_code'] ?? 0;
    if ($code === 201) return [true, json_decode($resp, true)];
    return [false, $resp];
}

function gh_upload_asset($uploadUrlTemplate, $filePath, $assetName, $token) {
    // uploadUrlTemplate: e.g. https://uploads.github.com/repos/:owner/:repo/releases/:id/assets{?name,label}
    $url = preg_replace('/\{\?name,label\}/', '', $uploadUrlTemplate) . '?name=' . rawurlencode($assetName);
    $fp = fopen($filePath, 'rb');
    if (!$fp) return [false, 'failed_open_file'];
    $fsize = filesize($filePath);

    $ch = curl_init();
    $headers = [
        'User-Agent: CaliusCMS/1.0',
        'Content-Type: application/zip',
        'Content-Length: ' . $fsize,
        'Authorization: token ' . $token
    ];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, $fsize);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if ($err) return [false, "curl: $err"];
    $code = $info['http_code'] ?? 0;
    if ($code >= 200 && $code < 300) return [true, json_decode($resp, true)];
    return [false, $resp];
}

/**
 * Deliver artifact to GitHub Release. Returns array with keys: ok(bool), message, data
 */
function deliver_artifact_to_github($artifactPath, $artifactFile, $artifactEntry = null) {
    $settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
    $d = $settings['artifactDelivery'] ?? [];
    if (!($d['enabled'] ?? false)) return ['ok' => false, 'message' => 'artifactDelivery disabled'];

    $ownerRepo = $d['repo'] ?? '';
    $tokenEnv = $d['tokenEnvVar'] ?? 'GITHUB_ARTIFACT_TOKEN';
    $token = getenv($tokenEnv) ?: null;
    if (!$token) return ['ok' => false, 'message' => 'missing_token', 'detail' => "env var $tokenEnv not found"];

    $tag = str_replace('{timestamp}', gmdate('YmdHis'), $d['tagFormat'] ?? 'templates-{timestamp}');
    $name = str_replace('{timestamp}', gmdate('Y-m-d H:i:s'), $d['releaseNameFormat'] ?? 'Templates - {timestamp}');

    // find or create release
    list($ok, $res) = gh_get_release_by_tag($ownerRepo, $tag, $token);
    if (!$ok) {
        list($ok2, $res2) = gh_create_release($ownerRepo, $tag, $name, $token);
        if (!$ok2) return ['ok' => false, 'message' => 'create_release_failed', 'detail' => $res2];
        $release = $res2;
    } else {
        $release = $res;
    }

    // upload asset
    $uploadUrl = $release['upload_url'] ?? null;
    if (!$uploadUrl) return ['ok' => false, 'message' => 'no_upload_url', 'detail' => $release];
    list($uok, $ures) = gh_upload_asset($uploadUrl, $artifactPath, $artifactFile, $token);
    if (!$uok) return ['ok' => false, 'message' => 'upload_failed', 'detail' => $ures];

    return ['ok' => true, 'message' => 'uploaded', 'data' => $ures, 'release' => $release];
}

?>