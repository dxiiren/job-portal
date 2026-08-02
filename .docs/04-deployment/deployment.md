# Deployment

> **TL;DR** There is none. No CI/CD, no hosting, no production environment — the app runs
> locally via `just start` on http://127.0.0.1:8108. This page records that honestly and
> lists what a real deploy would need.

## Current state

| Concern | State |
| --- | --- |
| CI (tests/lint on push) | None — `just lint` + `just test` run locally are the whole gate |
| CD / hosting | None — no server, no container, no platform config in the repo |
| Environments | One: your machine (`.env`, git-ignored) |
| Database | Local SQLite file (`database/database.sqlite`, git-ignored) |
| Uploaded CVs | Local private disk (`storage/app/private/cvs/`), never leaves the machine |
| Secrets | Only `APP_KEY`, generated locally by `just bootstrap` |

## If this ever deploys, it needs

1. A production `.env` — real `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, a real
   `APP_URL`, and a decision on DB (SQLite file vs MySQL/PostgreSQL — migrations use a
   DB-level enum for `experience`; verify enum behavior on the target engine).
2. A real web server (nginx/Apache/FrankenPHP) serving `public/` — not `artisan serve`.
3. `composer install --no-dev --optimize-autoloader` + `php artisan config:cache`,
   `route:cache`, `view:cache`.
4. `npm ci && npm run build` in the build pipeline (the layout needs
   `public/build/manifest.json` for real styling).
5. Durable storage for `storage/app/private/cvs/` (volume or object storage) + backups for
   the database.
6. A user-registration story — production can't seed its users.
7. Removal of dev-only conveniences: the debugbar package auto-enables with
   `APP_DEBUG=true` (set `DEBUGBAR_ENABLED=false` in your local git-ignored `.env` to hide
   it, e.g. when taking screenshots); seeded credentials must never ship.

## Related docs

| Doc | Why |
| --- | --- |
| [../02-setup/getting-started.md](../02-setup/getting-started.md) | The only "deployment" that exists today |
| [../01-overview/architecture.md](../01-overview/architecture.md) | What would actually be deployed |
| [../07-faq/faq.md](../07-faq/faq.md) | "Is this deployed anywhere?" and friends |
