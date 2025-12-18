<?php
/**
 * Helper to trigger GitHub Actions workflow via API
 * Returns array ['ok'=>bool,'message'=>..., 'detail'=>...] 
 */
function trigger_github_workflow($ownerRepo, $workflow, $ref = 'main', $token = null) {
    if (!$ownerRepo) return ['ok' => false, 'message' => 'missing_repo'];
    if (!$token) return ['ok' => false, 'message' => 'missing_token'];

    $url = "https://api.github.com/repos/{$ownerRepo}/actions/workflows/{$workflow}/dispatches";
    $body = json_encode(['ref' => $ref]);

    $ch = curl_init($url);
    $headers = [
        'User-Agent: CaliusCMS/1.0',
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $token,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $code = $info['http_code'] ?? 0;
    if ($err) return ['ok' => false, 'message' => 'curl_error', 'detail' => $err];
    if ($code >= 200 && $code < 300) return ['ok' => true, 'message' => 'dispatched', 'detail' => $resp];
    return ['ok' => false, 'message' => 'api_error', 'detail' => $resp, 'code' => $code];
}

?>
