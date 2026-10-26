# Operations

## Runtime
- `docker compose up --build` (MySQL 8.0 healthchecked, Redis 6.2, app on `${APP_PORT:-8080}:80`).
- Native: `composer install && npm ci && php artisan migrate --seed`.

## Queue
Default `.env.example` uses `QUEUE_CONNECTION=database`. For sync-dev keep `sync`, but production must run:
```
php artisan queue:table && php artisan migrate
php artisan queue:work --daemon
```
Notifications (`SupportRequest`, `StatusChange`, `NotCompleted`, …) are sent inline in several loops; without a worker they block requests.

## Scheduler (required)
`app/Console/Kernel.php` runs daily:
- `UserSupportData::cronEmails()` — reminder/escalation notifications.
- `UserSupportData::requestCompleted()` — auto-close stale requests.
Crontab:
```
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

## Backups
- Nightly `mysqldump` of `${DB_DATABASE}` + `storage/` snapshots. Test restores quarterly.
- After this refresh, compiled assets are gitignored; CI rebuilds via `npm run production`. Keep `public/mix-manifest.json` committed.

## Monitoring
- `LOG_LEVEL=warning` default; watch `storage/logs/laravel.log` for 403 spikes on AJAX routes (possible probing).
- Health: `mysqladmin ping` (compose healthcheck), queue length, scheduler heartbeat.
