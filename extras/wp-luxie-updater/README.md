Luxie Updater plugin (example)
=================================

This is an example WordPress plugin skeleton that demonstrates how a WP site (e.g., luxiephoto.com) can receive HMAC-signed webhooks from calius.digital and download template artifacts for review.

Install
-------
1. Copy the `luxie-updater` folder into `wp-content/plugins/` on your WordPress site.
2. Activate the plugin from the WordPress admin plugins screen.
3. Go to Settings → Luxie Updater and set the `Webhook secret` to match the `LUXIEPHOTO_WEBHOOK_SECRET` used by calius.digital's CI.

Notes
-----
- This is a minimal example and **should not** be used in production without adding backup, rollback, audit logging, and permission controls.
- The plugin enqueues a background job to download and verify artifacts; it does not auto-apply updates by default.
