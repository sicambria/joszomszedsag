# AGENTS.md — contributor & agent guide

## Stack
Laravel 12 + PHP 8.3 (pinned in CI), MySQL 8, Redis 6, Vite 7 + jQuery/Materialize (vendored).
Host PHP is 8.5 — **do not run composer/artisan locally**; use the `joszomszedsag-dev` container (PHP 8.3 + extensions + pcov):
```
docker build -f docker/dev/Dockerfile -t joszomszedsag-dev .
docker run --rm -v .:/app -w /app joszomszedsag-dev vendor/bin/phpunit
```

## Quickstart (native, PHP ^8.2 + composer)
```
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm ci && npm run build
php artisan serve
```

## Quickstart (docker)
```
cp .env.example .env
docker compose up --build
# app on localhost:${APP_PORT:-8080}; add joszomszedsag.local to /etc/hosts (see docker/Readme.md)
# one-shot test run: docker compose --profile test run --rm tests
```

## Tests / lint (must be green before merge)
```
docker run --rm -v .:/app -w /app joszomszedsag-dev vendor/bin/phpunit --coverage-text
npm ci && npm run build
npm run lint && npm audit --audit-level=high
```
CI: `.github/workflows/ci.yml` runs all of the above on PHP 8.3 + Node 20, and fails under 50% line coverage.

## Where to change what
- Public forms: `app/Http/Controllers/FrontIndexController.php` + `resources/views/forms/*` + `routes/web.php` (`postcode-autocomplete`, `form-submit`).
- Dashboard (verified users): `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard/**`.
- Bespoke admin mailer: `app/Http/Controllers/BulkMailController.php` + `resources/views/dashboard/bulk_mail/*` (admin-only).
- Roles: `App\Support\RoleIds` (`app/Support/RoleIds.php`) — never hardcode `1/6/7`. Admin=1, Volunteer=6, Requester=7 (legacy ids, seeded).
- Settings: `config/site.php` (+ `SITE_*` env) — replaces the removed Voyager settings table.
- Logs: `app/Helpers.php` (`logModelChange`, `saveLog`) → `App\Log`. Never log secrets; values are strip-tagged + truncated.
- View composer: `app/Http/View/Composers/LayoutComposer.php` — keep queries bound, guard nulls.

## Security rules (non-negotiable)
1. **Never** concatenate request/auth input into SQL. Use `?` bindings or query builder. (`DB::select('... ?', [$x])`).
2. **Never** return PII (`email/phone/facebook`) without an authorization check (owner, assigned volunteer, admin). Use `findOrFail` + explicit gate, return 403 otherwise.
3. Validate every public input (`validate()` + whitelist `in:6,7` for roles). Throttle public POSTs.
4. No secrets in repo. `.env` is gitignored; compose uses `${VAR:-default}` + `env_file: .env`.
5. `APP_DEBUG=false` in any non-local env. `SESSION_SECURE_COOKIE=true` behind HTTPS.

## Remotes (standing user preference)
- **Never touch `upstream` (`Kr4z4r/joszomszedsag`)**: no push, no PR, no fetch-required workflows against it.
- Work only with `origin` (`sicambria/joszomszedsag`): commit and push to `origin/master` unless told otherwise.

## Style
- PHP: Laravel Pint default (tabs→spaces, see `.editorconfig`); `php -l` clean.
- JS: ESM (`import`), `eslint` + `prettier` configs at root. Blade-injected globals (`M`, `ajaxPostRoute`, `ajaxMoreInfoRoute`, `csrfToken`) are declared in `.eslintrc.json` — don't add new ones.
- CSS: `resources/sass/**` via Vite; build output `public/build` is gitignored. Third-party dist files live in `public/vendor/` (committed).

## Git hooks
`hooks/pre-commit` runs `php -l` on staged PHP + eslint on staged JS. Enable with:
```
git config core.hooksPath hooks
```
