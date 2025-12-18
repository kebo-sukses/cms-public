<?php
/**
 * Plugin Name: Luxie Updater (Calius Integration)
 * Description: Example plugin to accept HMAC-signed webhooks from calius.digital and download template artifacts for review or auto-apply.
 * Version: 0.1
 * Author: Calius Digital
 */

if (!defined('ABSPATH')) exit;

class Luxie_Updater {
    const OPTION_SECRET = 'luxie_updater_secret';

    public static function init() {
        add_action('rest_api_init', function() {
            register_rest_route('calius/v1', '/remote-update', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'handle_remote_update'],
                'permission_callback' => '__return_true'
            ]);
        });

        add_action('admin_menu', function() {
            add_options_page('Luxie Updater', 'Luxie Updater', 'manage_options', 'luxie-updater', [__CLASS__, 'settings_page']);
        });
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['luxie_updater_secret'])) {
            update_option(self::OPTION_SECRET, sanitize_text_field($_POST['luxie_updater_secret']));
            echo '<div class="updated"><p>Saved</p></div>';
        }
        $secret = esc_attr(get_option(self::OPTION_SECRET, ''));
        echo '<div class="wrap"><h1>Luxie Updater</h1><form method="post"><label>Webhook secret</label><input type="text" name="luxie_updater_secret" value="' . $secret . '" style="width:400px" /><p><button class="button button-primary">Save</button></p></form></div>';
    }

    public static function handle_remote_update($request) {
        $secret = get_option(self::OPTION_SECRET);
        if (!$secret) return new WP_REST_Response(['success' => false, 'message' => 'Webhook secret not configured'], 500);

        $payload = $request->get_body();
        $sig = $request->get_header('x-signature');
        if (!$sig) return new WP_REST_Response(['success' => false, 'message' => 'Missing signature'], 400);

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $sig)) return new WP_REST_Response(['success' => false, 'message' => 'Invalid signature'], 401);

        $data = json_decode($payload, true);
        if (!$data) return new WP_REST_Response(['success' => false, 'message' => 'Invalid JSON'], 400);

        // Log update request
        $log = get_option('luxie_updater_log', []);
        $entry = [ 'received_at' => gmdate('c'), 'payload' => $data, 'status' => 'received' ];
        $log[] = $entry;
        update_option('luxie_updater_log', $log);

        // Enqueue background job (wp_cron or immediate) to fetch artifact and verify
        wp_schedule_single_event(time() + 5, 'luxie_updater_process', [$data]);

        return new WP_REST_Response(['success' => true, 'message' => 'Update queued for review'], 202);
    }
}

add_action('init', ['Luxie_Updater','init']);

// Background processing hook
add_action('luxie_updater_process', function($data) {
    // Basic processing: download artifact to uploads, verify checksum
    $upload_dir = wp_upload_dir();
    $dest = trailingslashit($upload_dir['basedir']) . 'calius-artifacts/';
    wp_mkdir_p($dest);
    $file = $dest . basename($data['artifact']);
    $url = $data['artifact_url'];
    // Use wp_remote_get
    $resp = wp_remote_get($url, ['timeout' => 60]);
    if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
        // log failure
        $log = get_option('luxie_updater_log', []);
        $log[] = ['time' => gmdate('c'), 'action' => 'download_failed', 'url' => $url, 'error' => wp_remote_retrieve_response_message($resp)];
        update_option('luxie_updater_log', $log);
        return;
    }
    file_put_contents($file, wp_remote_retrieve_body($resp));
    // verify sha256 if provided
    if (!empty($data['sha256'])) {
        $sha = hash_file('sha256', $file);
        if ($sha !== $data['sha256']) {
            $log = get_option('luxie_updater_log', []);
            $log[] = ['time' => gmdate('c'), 'action' => 'checksum_mismatch', 'expected' => $data['sha256'], 'actual' => $sha];
            update_option('luxie_updater_log', $log);
            unlink($file);
            return;
        }
    }
    // At this point, artifact is downloaded and verified. Admin can review in WP (not auto-applied in this stub).
    $log = get_option('luxie_updater_log', []);
    $log[] = ['time' => gmdate('c'), 'action' => 'downloaded', 'file' => $file];
    update_option('luxie_updater_log', $log);
});
