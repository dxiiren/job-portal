# TL;DR — every doc in 30 seconds

One paragraph per document. Read this page, then jump to what you need.

## [01-overview/project-overview.md](01-overview/project-overview.md)

Job Portal is a Laravel 12 demo app around one domain: jobs, applicants, and employers.
Browse a seeded catalog (103 jobs), filter by search / salary range / experience /
category, sign in (seeded accounts only — `akmal@gmail.com` / `password` as a job seeker,
`employer@gmail.com` / `password` as an employer), apply with an
expected salary + PDF CV, or register as an employer and manage your own listings. The
value is in the policy-driven authorization, the composable `scopeFilter`, and the
FormRequest/upload patterns. Runs locally on SQLite at http://127.0.0.1:8108.

## [01-overview/architecture.md](01-overview/architecture.md)

Server-rendered MVC: `routes/web.php` (public jobs + an `auth` group) → six resource
controllers guarded by policies (`JobPolicy`, `EmployerPolicy`) and `EmployerMiddleware` →
Eloquent models — `Job` lives on the **`offered_jobs`** table (stock `jobs` belongs to the
queue), soft-deletes, and carries `scopeFilter` — → Blade component views (`<x-layout>`,
`<x-job-card>`) built by Vite/Tailwind 4. Writes flow through FormRequests; CVs land on the
private `local` disk. Seeding: 302 users / 21 employers / 103 jobs / random applications,
plus two deterministic demo logins (seeker + employer).

## [02-setup/getting-started.md](02-setup/getting-started.md)

Five steps on a stock Windows machine: `pwsh ./setup.ps1` (installs Git, Node, PHP 8.4,
Composer, uv, just — idempotent), reopen PowerShell, `just bootstrap` (deps + `.env` +
sqlite + migrate + Vite build), `just fresh` to seed (required for login), `just start` →
http://127.0.0.1:8108. Everything is safe to re-run.

## [03-development/workflow.md](03-development/workflow.md)

Branch off `main`, `just serve` while editing (no restarts), follow the house patterns
(FormRequest + policy for writes, `scopeFilter` for queries, factories for data, `<x-layout>`
components for pages), gate with `just lint` + `just test` (no CI — that's the whole gate),
Conventional Commits, PR via `gh`. Sharp edges: `offered_jobs` naming, `just fresh` wipes
data, the length-based salary validation quirk. Tests run on sqlite `:memory:` and the
suite is green — keep it that way.

## [04-deployment/deployment.md](04-deployment/deployment.md)

There is no deployment — no CI/CD, no hosting, local `just start` only; this doc says so
honestly. It also lists what a real deploy would need: production `.env` + real web server,
optimized composer/artisan caches, `npm run build` in the pipeline, durable storage for CVs
and the DB, and a user-registration story.

## [05-reference/commands.md](05-reference/commands.md)

The `just` recipe table: `bootstrap`, `start`/`serve`/`stop` (project-scoped kill),
`migrate`/`fresh`, `test`, `lint`/`lint-fix`, `claudex/o/h` — plus the occasional raw
artisan commands worth knowing (`route:list`, `tinker`, `migrate:status`, `db:seed`).
PHP/Composer resolve by absolute path so recipes work in stale shells.

## [05-reference/project-layout.md](05-reference/project-layout.md)

Annotated tree of the repo: ~25 domain PHP files (6 controllers, 4 models, 2 policies, 4
FormRequests, 2 enums, 1 middleware) on the stock Laravel 12 skeleton, 18 Blade
views/components, factories/seeder, and the onboarding kit (`justfile`, `setup.ps1`,
`.docs/`, `.claude/`). Ends with a "where to make which change" table (filters, fields,
policies, styling, pages, seed shape).

## [06-troubleshooting/common-issues.md](06-troubleshooting/common-issues.md)

Real symptom → cause → fix entries from the verification runs: empty `/jobs` (seed with
`just fresh`), the historical Pint debt (now cleared), missing PHP
install, unstyled pages (no Vite manifest), port 8108 conflicts and the multi-worker
`php.exe` shape, the historical tests-eating-seeded-data trap (phpunit DB overrides are
active now), and the VC++/winget UAC hang the setup script already guards against.

## [07-faq/faq.md](07-faq/faq.md)

Quick answers: port 8108 (`$env:PORT` to override), how to log in without a register page,
trying the employer side, why a job with applications can't be edited, one-application-per-
job, where CVs go, the `offered_jobs` table name, seed data, `start` vs `serve`, why `stop`
kills several processes, the two expected clean-clone failures, the filter list, and "is
this deployed?" (no).
