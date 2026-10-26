
# Jószomszédság — help your neighbors

Open-source multilingual portal for neighborhood cooperation (volunteers ↔ people in need). AGPL-3.0.

![CI](https://github.com/Kr4z4r/joszomszedsag/actions/workflows/ci/badge.svg)
![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)

## Stack
Laravel 7 (PHP 7.4 in CI), MySQL 8, Redis 6, jQuery/Materialize via Laravel Mix 5.

> Host PHP here is 8.5 — **run the app via CI (PHP 7.4) or `docker compose`**, not local `php artisan`.

## Quickstart (native)
```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm ci && npm run dev
php artisan serve
```

## Quickstart (docker)
```bash
cp .env.example .env
docker compose up --build
# app on localhost:${APP_PORT:-8080}; add joszomszedsag.local to /etc/hosts (see docker/Readme.md)
```

## Testing / lint
```bash
vendor/bin/phpunit --testdox
find app routes database -name '*.php' -print0 | xargs -0 -n1 php -l
npm ci && npm run production
npm audit --audit-level=high
```
CI (`.github/workflows/ci.yml`) runs all of the above on PHP 7.4 + Node 18.

## Docs
- `AGENTS.md` — contributor/agent guide (read first).
- `BACKLOG.md` — full refresh backlog (P0 done, P4 deferred).
- `docs/SECURITY.md` — reporting, threat model, operator checklist.
- `docs/OPERATIONS.md` — queue, scheduler, backups.
- `docs/UPGRADE-PLAN.md` — deferred Laravel 12 path.

## Project
- Why/what/who: neighborhood self-organization, elders, families, single parents.
- Contribute: UX/UI, web dev, testers, translators — see BACKLOG P4 for i18n plans.

## License
AGPL-3.0 — see https://www.gnu.org/licenses/agpl-3.0.en.html
