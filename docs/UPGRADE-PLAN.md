# Upgrade log — Laravel 12 / PHP 8.3 (EXECUTED 2026-10-27)

This plan was executed (fresh-skeleton port, not stepwise). Kept for history;
see BACKLOG.md P4 for item-by-item status.

## What was done
1. **Fresh `laravel/laravel:^12` skeleton** adopted (`bootstrap/app.php`, `bootstrap/providers.php`,
   slim `config/*`, `public/index.php`, `artisan`, `phpunit.xml`, `vite.config.js`).
   Old `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php`,
   `RouteServiceProvider` (now a `HOME` const holder), `server.php`, `routes/api.php`,
   `routes/channels.php`, Voyager configs/views/seeds deleted.
2. **Voyager removed** (`tcg/voyager` abandoned, archived). Replaced by:
   - `App\Role` model + `roles` migration + `DatabaseSeeder` (ids 1/6/7),
   - `config/site.php` (+ `SITE_*` env) replacing `Voyager::setting()` / `setting()` in views + notifications,
   - bespoke `/admin/korlevelek` CRUD (`BulkMailController` + views) replacing BREAD,
   - static dashboard sidebar + footer menus replacing `menu()`.
3. **Full migrations written** for all BREAD-only tables (roles, lookups, addresses,
   users extension incl. nullable `password`, volunteer/support tables, pivot FK alignment,
   logs, bulk_mails, jobs/job_batches). FK-safe order; sqlite-tested.
4. **Framework deltas**: `fideloper/proxy` dropped (framework TrustProxies), `fruitcake/cors`
   dropped (framework HandleCors defaults), `facade/ignition` → `spatie/laravel-ignition`,
   `fzaninotto/faker` → `fakerphp/faker` (class factories), `laravel/ui ^4.6` with explicit
   auth routes (no `Auth::routes` magic), schedule moved to `routes/console.php`.
5. **Frontend**: Mix 5/webpack 4 → Vite 7 (`laravel-vite-plugin ^2`, `vite ^7`), ESM imports,
   `@vite` in Blade, vendored ArchitectUI dist moved to `public/vendor/` (committed),
   build output `public/build/` gitignored.
6. **Tests**: 38 tests, ~70% line coverage (gate: 50% in CI).

## Follow-ups (not done)
- 2FA / Turnstile on public forms; queued mail in prod (config default is `database`, workers per OPERATIONS.md).
- Filament evaluation if the bespoke admin outgrows itself.
- i18n extraction to `lang/hu|en` (MOD-06 partial: validation messages still inline).
