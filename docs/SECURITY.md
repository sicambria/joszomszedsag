# Security

## Reporting
Do not open public issues for suspected vulnerabilities. Contact the maintainers privately (see README) with: affected route/file, reproduction steps, impact. Expect acknowledgement within 5 business days.

## Threat model (this app)
- Public forms collect volunteer/requester PII (name, email, phone, address, Facebook). Protect accordingly.
- Authenticated dashboard exposes requester contact details — disclosure is gated to owner / assigned volunteer / admin only.
- Legacy admin (Voyager, ` /administration/*`) is a high-value target; keep it behind strong passwords + HTTPS.

## Hardening already applied (2026-10 refresh)
- All SQL uses parameter binding; no string-concatenated queries.
- AJAX endpoints validate input, use `findOrFail`, and enforce ownership/admin checks (403 otherwise). PII redacted for unauthorized callers.
- Public POSTs throttled (`throttle:10,1` / `30,1`); autocomplete limited to single-digit prefix + `LIMIT 20`.
- `APP_DEBUG=false` default in `.env.example`; `SESSION_HTTP_ONLY=true`; strong-password placeholders.
- Logs strip tags + truncate to 200 chars; passwords/`remember_token` never logged.
- Docker images pinned (`mysql:8.0.36`, `redis:6.2-alpine`, `php:7.4.33-fpm-alpine3.16`); proxy socket mount removed; compose reads `${VAR}` + `env_file`.

## Known residual risks
- Laravel 7 / PHP 7.4 are EOL (no security patches). Upgrade path: `docs/UPGRADE-PLAN.md` (MOD-01).
- `tcg/voyager ^1.5` is abandoned with published CVEs; restrict `/administration/*` by IP/VPN if exposed.
- `public/adminer.php` was committed then removed (`da7e74a`); any DB creds live at that time must be considered compromised — rotate.
- Sync mail/notify in request loops can time out; move to queue (see OPERATIONS.md).

## Operator checklist
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, fresh `APP_KEY`.
- [ ] Unique `DB_PASSWORD`, `SESSION_SECURE_COOKIE=true` behind HTTPS.
- [ ] `composer audit` / `npm audit --audit-level=high` clean.
- [ ] Voyager admin credentials rotated; `/administration` not indexed.
