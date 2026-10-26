# Upgrade plan — Laravel 12 / PHP 8.3 (DEFERRED, not in harden-in-place pass)

## Why deferred
Voyager has no Laravel 11/12 release; BREAD schema lives in seeders, not migrations; Mix 5/webpack 4 + vendored Materialize need a frontend rewrite. Doing this blind risks a non-booting app. Harden-in-place (BACKLOG P0–P3) comes first.

## Suggested path
1. **Baseline**: green CI on PHP 7.4 (this branch). Freeze feature work.
2. **Test coverage**: expand `tests/Feature` to cover every dashboard route + mail flows (sqlite first, then MySQL in CI).
3. **DB truth**: reverse-engineer Voyager/BREAD tables into real migrations (`migrate:generate` or manual), add FKs. Remove seed-only schema.
4. **Admin replacement**: replace `tcg/voyager` with Filament (or bespoke admin). Migrate roles/permissions (`RoleIds` constants already isolate magic ids).
5. **Framework jump**: Laravel 7 → 8 → 9 → 10 → 11 → 12 stepwise (shift-style), PHP 7.4 → 8.3. At each step: `composer update`, fix deprecations, run suite.
   - `fruitcake/laravel-cors` → built-in; `fideloper/proxy` → `TrustProxies` middleware changes; `swiftmailer` → Symfony Mailer; `phpunit.xml` whitelist → coverage.
6. **Frontend**: Mix 5 → Vite, drop vendored Materialize/jQuery copies, `mix()` → `vite()`, Tailwind or updated Materialize via npm.
7. **Auth/mail**: verify `MustVerifyEmail` flows, queue all notifications, add reCAPTCHA/Turnstile to public forms, 2FA for admin.
8. **i18n**: extract Hungarian strings to `resources/lang/hu|en`, crowdsourced translation pipeline (MOD-06).

## Effort estimate
3–6 engineer-weeks including manual QA of volunteer/requester flows. Do not attempt without a staging DB snapshot.
