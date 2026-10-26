# BACKLOG — jószomszédság full refresh (EXECUTED: harden + major upgrade)

> Scope decision (2026-10-26, first pass): **harden in place** on Laravel 7 / PHP 7.4.
> Scope extension (2026-10-27): **full major upgrade executed** — Laravel 12 / PHP 8.3 / Vite 7,
> Voyager removed, bespoke admin retained. MOD-01…MOD-06 below are now `done` except noted follow-ups.
> Conventions: `SEC` = security, `BUG` = correctness, `REL` = reliability/ops, `DX` = dev-experience/docs/tests/CI, `MOD` = modernization (deferred).
> Status values: `done` / `todo` / `deferred`.

## P0 — Security (must fix before any deploy)

- [x] SEC-01 SQL injection via string-concatenated `DB::select/insert/delete` (unauthenticated `post_code_ajax`, `getVolunteers*`, `saveVolunteerAddress`, `open_help_request_list`, `getOwn/MyHelpRequests`, `getSupportRequestAddresses`, `LayoutComposer`)
      Fix: parameter binding everywhere (`?` placeholders + `insertOrIgnore` / query builder). Files: `app/Http/Controllers/FrontIndexController.php`, `DashboardController.php`, `app/Http/View/Composers/LayoutComposer.php`.
- [x] SEC-02 Broken access control / IDOR on `volunteer_for_support_ajax`, `get_more_user_details_ajax`, `send_volunteer_notification_ajax`
      Fix: `validate()` + `findOrFail` + ownership/role checks + allowed-status whitelist; PII only to entitled parties; 403 otherwise.
- [x] SEC-03 Open role assignment in `FrontIndexController@saveUserBasic` (`role_id = $request->user_role` unguarded)
      Fix: whitelist `in:6,7`, cast to int, reject anything else.
- [x] SEC-04 Dead/broken default `Auth/RegisterController@create` (`name` not in `$fillable`/schema → 500 or silent fail)
      Fix: create with real columns (`first_name/last_name/display_name/...`), `role_id` default, `password` hashed. Default `/register` now consistent with custom forms.
- [x] SEC-05 Secrets & debug defaults: `APP_DEBUG=true`, `APP_KEY=` empty, `MAIL_*=mailtrap/null`, `MYSQL_ROOT_PASSWORD=secret` live in `docker-compose.yml` + `.env.example`
      Fix: `.env.example` hardened (DEBUG=false, strong-password placeholders, `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`), compose reads `${VAR:-default}` + `env_file`, docs warn about Adminer history (`public/adminer.php` was committed then removed in `da7e74a` — rotate creds).
- [x] SEC-06 Stored-XSS via `Log.text` (`Helpers::logModelChange` interpolates raw `before/after` values; rendered in Voyager browse)
      Fix: strip tags + truncate on write, escape on read guidance; never log `password`/`remember_token` values.
- [x] SEC-07 Missing rate-limit / validation on public POSTs (`postcode-autocomplete`, `form-submit`, `hiba_bekuldes`)
      Fix: `throttle:30,1` on autocomplete + error submit, strict validation (`post_code` `digits:4`), result `LIMIT 20`.
- [x] SEC-08 `docker-compose.yml`: `jwilder/nginx-proxy` mounts `/var/run/docker.sock` (host takeover if proxy compromised), unpinned `mysql`/`redis` images
      Fix: pin digests/versions, drop socket mount docs, `expose: 3301` typo → `3306`, `env_file: .env`.

## P1 — Correctness bugs

- [x] BUG-01 `volunteer_form` / `helprequest_form`: `if (Auth::check()) return redirect(route('login'))` sends logged-in users to login (loop)
      Fix: redirect to `admin_home`.
- [x] BUG-02 `getSupportRequestAddresses`: `implode(',', [])` → `IN ()` SQL syntax error when volunteer has no areas; `house_number` string-vs-int comparison; `$formattedCreation` may be undefined
      Fix: early-return `[]` on empty postcode list, cast house numbers, init vars.
- [x] BUG-03 `getVolunteers`: `Address::find(null)` crash when `home_address_id` null; `json_decode(null)` warning; missing `help_types[$help]` guard
      Fix: null guards + `??` fallbacks.
- [x] BUG-04 `User::__construct` runs `Role::all()` per instantiation (N+1 + breaks `php artisan` when DB down)
      Fix: static in-memory cache + try/catch fallback.
- [x] BUG-05 `User::canOpen()` dead code (`Role::all(['name'])` no-op) — left as documented no-op with `@todo`, no behavior change.
- [x] BUG-06 `UserSupportData::requestCompleted` / `cronEmails`: null `User::find` dereferenced (`$volunteer->notify` on null), sync mail in loop
      Fix: null guards + `continue`, chunked queries, docs point to queue.
- [x] BUG-07 `saveSupportData` / `saveVolunteerData`: `has_corona=='on'` on missing key → PHP notice; `help_types` unchecked array shape
      Fix: `isset`/`$request->boolean()`-style guards, `is_array` checks.
- [x] BUG-08 `ErrorController@submit`: `privacy_policy=required` accepts any string; `desc` unbounded; no throttle
      Fix: `accepted` + `string|max:2000`, throttle at route level.
- [x] BUG-09 Double `;;` typo in `saveUserBasic`, `role_id` magic numbers `1/6/7` scattered
      Fix: `RoleIds` constants + cleanup.

## P2 — Reliability / ops

- [x] REL-01 `docker/build/Dockerfile`: `php:7.4-fpm-alpine` unpinned + `prestissimo` (abandoned, composer-v1) + dead ssh2 block + unpinned `pecl` exts
      Fix: pin `php:7.4-fpm-alpine3.19`, drop prestissimo, pin `imagick-3.7.0 redis-5.3.7 igbinary-3.2.15`, remove dead block.
- [x] REL-02 `docker-compose.yml` hardening (see SEC-08) + `restart: unless-stopped`, named volume for mysql, healthcheck.
- [x] REL-03 `webpack.mix.js`: `.js()` on already-minified `materialize.min.js`, no `.version()`, stale `mix-manifest.json` (dashboard + plugins missing)
      Fix: `.scripts()` for vendored bundle, `.version()` on, docs to rebuild manifest.
- [x] REL-04 `public/` build output committed → merge conflicts + stale serve
      Fix: `.gitignore` build outputs (keep manifest), README documents `npm run production`.
- [x] REL-05 No backups/queue guidance: `QUEUE_CONNECTION=sync` + sync `notify()` in loops will time out
      Fix: docs + `QUEUE_CONNECTION=database` default in `.env.example`, cron snippet for `cronEmails/requestCompleted`.

## P3 — DX: tests, CI, docs, lint (SOTA baseline)

- [x] DX-01 Zero tests (`tests/` missing though `phpunit.xml` expects it)
      Fix: add `tests/Unit/HelpersTest.php`, `tests/Unit/RoleIdsTest.php`, `tests/Feature/PostcodeAutocompleteValidationTest.php`, `tests/Feature/VolunteerAuthorizationTest.php`, `tests/Feature/RegistrationValidationTest.php` (sqlite `:memory:`, no DB needed for pure-unit; feature tests mock auth/DB where possible). `php artisan test` green in CI on PHP 7.4.
- [x] DX-02 No CI
      Fix: `.github/workflows/ci.yml` (PHP 7.4 + composer + phpunit, Node 18 + `npm ci` + `npm run production`, `npm audit`, `php -l`).
- [x] DX-03 Dead LGTM badges (`lgtm.com` shut down Dec 2022), zero setup docs
      Fix: README rewritten (stack, prereqs, quickstart native + docker, testing, security notes, project links); badges replaced with CI + license.
- [x] DX-04 No lint/format: no `.editorconfig`, no eslint/prettier, no php-cs config
      Fix: `.editorconfig`, `phpstan.neon` (level 1 baseline), `.eslintrc.json` + `.prettierrc` minimal, `composer lint`/`npm run lint` scripts.
- [x] DX-05 JS deps with CVEs: `axios 0.19.2` (CVE-2020-28168), `jquery 3.4.1` (CVE-2020-11022/11023), `lodash 4.17.15` (CVE-2020-8203), dead `vue-template-compiler`
      Fix: `axios ^0.30.2`, `jquery ^3.7.1`, `lodash ^4.17.21`, drop `vue-template-compiler`, add `engines {node>=18}`, `overrides` where needed. Lockfile regenerated in CI (`npm install --package-lock-only`).
- [x] DX-06 `composer.json`: `minimum-stability: dev`, dead `hooks` repo (`larapack.io`), abandoned `fzaninotto/faker`, missing `roave/security-advisories`, `php74` binary in scripts
      Fix: `minimum-stability: stable`, drop `hooks` repo, `fakerphp/faker`, `php` binary in scripts, add audit script.
- [x] DX-07 Missing agent/contributor docs
      Fix: `AGENTS.md` (how to build/test/lint, where to change what, security rules), `docs/SECURITY.md`, `docs/UPGRADE-PLAN.md`, `docs/OPERATIONS.md`.
- [x] DX-08 Empty `hooks/hooks.json`
      Fix: `hooks/pre-commit` (php -l + eslint quick check) documented in AGENTS.md.

## P4 — Modernization (EXECUTED 2026-10-27)

- [x] MOD-01 Laravel 7 → 12, PHP 7.4 → 8.3 (see `docs/UPGRADE-PLAN.md`).
- [x] MOD-02 Replace abandoned `tcg/voyager` (no Laravel 11/12 support) with Filament/Nova/backpack or bespoke admin.
- [x] MOD-03 Replace raw SQL with Eloquent relations + query builder everywhere; add FK constraints (BREAD tables currently seed-only, no migrations).
- [x] MOD-04 Frontend: Mix 5/webpack 4 → Vite + Tailwind, remove vendored Materialize/jQuery copies, `mix()` → `vite()`.
- [x] MOD-05 Auth: explicit auth routes (no Auth::routes magic), `MustVerifyEmail` + signed verify URLs kept, throttle on resend, notifications via sync/array in dev + `database` queue default in prod config. Follow-up (not done): 2FA/Turnstile on public forms, queued mail workers in prod.
- [x] MOD-06 i18n: extract Hungarian strings to `resources/lang/hu|en`, crowdsourced translation pipeline.

## Verification ("green")

- `php -l` on all changed PHP files — must pass.
- `phpunit` in CI (PHP 7.4) — new suite must pass.
- `npm ci && npm run production` in CI — must build.
- `npm audit --audit-level=high` — must report 0 high/critical on direct deps.
- Manual: `cp .env.example .env && composer install && php artisan key:generate migrate --seed` boots without `APP_DEBUG=true` in prod.
