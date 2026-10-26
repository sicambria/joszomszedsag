# Docker Setup Guide

For tech-savvy people or developers looking to contribute.

## Prerequisites
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
- [Docker](https://docs.docker.com/get-docker/) (Compose v2 included)
- [Node.js + npm](https://nodejs.org/en/download/) (for frontend builds)

Stop any services running on `${APP_PORT:-8080}`.

## Running the stack

```bash
git clone <origin-url> joszomszedsag
cd joszomszedsag
cp .env.example .env
docker compose up --build
```

App: `http://localhost:${APP_PORT:-8080}` (default 8080).
Optional `joszomszedsag.local` alias — add to `/etc/hosts`:

```text
127.0.0.1	joszomszedsag.local
```

and set `VIRTUAL_HOST=joszomszedsag.local` in `.env`.

## Useful commands

- Stop: `docker compose stop` (or Ctrl+C)
- Rebuild: `docker compose down && docker compose up --build`
  (!) `down` without `-v` keeps mysql/redis data volumes.
- Run the test suite (PHP 8.3 + pcov): `docker compose --profile test run --rm tests`
- Fresh DB: `docker compose exec app php artisan migrate:fresh --seed`
