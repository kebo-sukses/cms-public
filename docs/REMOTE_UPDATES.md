# Remote Updates (templates) — Integration Guide

This document describes the minimal integration to deliver template updates from calius.digital (CMS) to third-party WordPress sites such as luxiephoto.com.

Overview
--------
- The CMS build workflow creates ZIP artifacts for templates and uploads them as GitHub Release assets.
- The workflow sends an HMAC-signed webhook to each registered site with metadata and an artifact URL.
- The WordPress site validates the webhook signature, downloads the artifact, verifies the checksum, creates backups, and either queues the update for review or applies it (per policy).

Security
--------
- Use a shared secret (`LUXIEPHOTO_WEBHOOK_SECRET`) to compute HMAC-SHA256 signatures over the webhook JSON body.
- The header key is `X-Signature` with value `sha256=<hex>`.
- Use HTTPS for all endpoints and short-lived signed artifact URLs where possible.

Webhook payload example
-----------------------
```json
{
  "site": "calius-digital/cms-public",
  "artifact": "corporate-pro.zip",
  "artifact_url": "https://github.com/.../releases/download/templates-<sha>/corporate-pro.zip",
  "sha256": "<hex>",
  "version": "<sha>",
  "timestamp": "2025-12-17T...Z",
  "autoApply": false
}
```

Receiver stub
-------------
- A minimal PHP stub is included at `admin/api/remote-update.php`. It:
  - Validates `X-Signature` HMAC using `REMOTE_UPDATE_SECRET` environment variable.
  - Logs the payload to `data/remote_updates.json` with timestamp and `received` status.
  - Returns `202 Accepted` for valid payloads.

Next steps for WP integration
----------------------------
1. Implement a WordPress plugin that provides:
   - Admin UI for pairing (entering `WEBHOOK_SECRET`) and viewing pending updates.
   - Endpoint to accept webhook (use WP REST API with permission checks).
   - Backup/restore logic for files & DB prior to applying updates.
   - Option to auto-apply updates if allowed by policy.
2. Test end-to-end using a staging URL and rotate secrets after verification.

Operational checklist
---------------------
- Add `LUXIEPHOTO_WEBHOOK_URL` and `LUXIEPHOTO_WEBHOOK_SECRET` to GitHub repository secrets for calius.digital.
- Confirm artifact release permissions and accessibility to luxiephoto servers.
- Define policy: default `autoApply=false` (manual review), allow per-site opt-in for auto-apply.

Contact & Support
-----------------
If you want, I can implement the WordPress plugin skeleton and finish wiring the end-to-end flow (download/verify/apply + UI).  
