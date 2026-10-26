# AGENTS.md — contributor & agent guide

## Stack
Laravel 7 + PHP 7.4 (pinned in CI), MySQL 8, Redis 6, jQuery/Materialize via Laravel Mix 5.
Host PHP here is 8.5 — **do not `php artisan` locally**; use CI (PHP 7.4) or `docker compose` for runtime.

## Quickstart (native)
```
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm ci && npm run dev
php artisan serve
```

## Quickstart (docker)
```
cp .env.example .env
docker compose up --build
# app on joszomszedsag.local:80 (see docker/Readme.md + hosts entry)
```

## Tests / lint (must be green before merge)
```
php -l app/Http/Controllers/*.php app/*.php app/Helpers.php
vendor/bin/phpunit --testdox
npm ci && npm run production
npm audit --audit-level=high
```
CI: `.github/workflows/ci.yml` runs all of the above on PHP 7.4 + Node 18.

## Where to change what
- Public forms: `app/Http/Controllers/FrontIndexController.php` + `resources/views/forms/*` + `routes/web.php` (`postcode-autocomplete`, `form-submit`).
- Dashboard (verified users): `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard/**`.
- Roles: `App\Support\RoleIds` (`app/Support/RoleIds.php`) — never hardcode `1/6/7`. Admin=1, Volunteer=6, Requester=7 (legacy Voyager ids).
- Logs: `app/Helpers.php` (`logModelChange`, `saveLog`) → `App\Log`. Never log secrets; values are strip-tagged + truncated.
- View composer: `app/Http/View/Composers/LayoutComposer.php` — keep queries bound, guard nulls.

## Security rules (non-negotiable)
1. **Never** concatenate request/auth input into SQL. Use `?` bindings or query builder. (`DB::select('... ?', [$x])`).
2. **Never** return PII (`email/phone/facebook`) without an authorization check (owner, assigned volunteer, admin). Use `findOrFail` + explicit gate, return 403 otherwise.
3. Validate every public input (`validate()` + whitelist `in:6,7` for roles). Throttle public POSTs (`throttle:30,1`).
4. No secrets in repo. `.env` is gitignored; compose uses `${VAR:-default}` + `env_file: .env`.
5. `APP_DEBUG=false` in any non-local env. `SESSION_SECURE_COOKIE=true` behind HTTPS.

## Style
- PHP: Laravel preset (StyleCI). `php -l` clean, `phpstan` level 1 clean.
- JS: `eslint` + `prettier` configs at root; jQuery legacy in `resources/js/front.js` — don't add new global `$` code, prefer small modules.
- SCSS: `resources/sass/**`; rebuild with `npm run production` (manifest is committed, build output is gitignored).

## Git hooks
`hooks/pre-commit` runs `php -l` on staged PHP + eslint on staged JS. Enable with:
```
git config core.hooksPath hooks
```

## Remotes (standing user preference)
- **Never touch `upstream` (`Kr4z4r/joszomszedsag`)**: no push, no PR, no fetch-required workflows against it.
- Work only with `origin` (`sicambria/joszomszedsag`): commit and push to `origin/master` unless told otherwise.

## Deferred modernization
Do NOT upgrade Laravel/Voyager/Mix in this branch. Plan lives in `docs/UPGRADE-PLAN.md` (Laravel 12, Filament, Vite). Keep changes backport-safe to Laravel 7 / PHP 7.4 syntax (no PHP 8-only syntax, no named args).
