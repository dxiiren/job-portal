# Project overview

> **TL;DR** Job Portal is a Laravel 12 learning/demo app: a seeded catalog of job listings
> with search and salary/experience/category filters, session-based login, CV-upload job
> applications, and an employer side for posting and managing listings. It runs locally
> only, on SQLite, at http://127.0.0.1:8108.

## What it is

A small, complete Laravel 12 application built around one domain: **jobs, the people who
apply to them, and the employers who post them**. There is no admin panel and no external
API — the interesting parts are the policy-driven authorization, the `Job::scopeFilter`
query composition, the FormRequest validation (including a private-disk PDF upload), and
soft-deleted listings.

## What it does

| Feature | Where |
| --- | --- |
| Home — redirects to the job list | `GET /` → `jobs.index` |
| Job list — paginated, search (title/description/company) + min/max salary + experience + category filters | `GET /jobs` |
| Job detail — description, tags, employer's other listings | `GET /jobs/{job}` |
| Sign in / sign out (session auth; users come from the seeder) | `GET /auth/create`, `POST /auth`, `DELETE /logout` |
| Apply to a job — expected salary + PDF CV (private disk), one application per user per job | `GET/POST /job/{job}/application/...` |
| My applications — per-job stats (application count, average expected salary), withdraw | `GET /my-job-applications`, `DELETE /my-job-applications/{id}` |
| Become an employer — register a company name (once) | `GET/POST /employer/...` |
| My jobs — employer CRUD on own listings, incl. soft-deleted ones | `/my-jobs` (full resource) |

## Key design points

- **Policies own the rules** — `JobPolicy` (create requires an employer account, update/delete
  require ownership, update is denied once applications exist, `applyJob` blocks duplicate
  applications) and `EmployerPolicy` (one employer account per user), both registered in
  `AppServiceProvider` and enforced with `can:` controller middleware.
- **`offered_jobs`, not `jobs`** — the `Job` model maps to the `offered_jobs` table because
  the stock Laravel migration already claims `jobs` for the queue backend.
- **Composable filtering** — `Job::scopeFilter` chains `when()` clauses for search (title,
  description, or employer company name), salary bounds, experience enum and category enum;
  the paginator `appends()` the query string so filters survive page links.
- **House validation pattern** — writes go through FormRequests (`JobRequest` with enum
  rules, `StoreJobApplicationRequest` with `cv` = `file|mimes:pdf|max:2048`), never raw
  `$request->input()` persistence. CVs land on the **private** `local` disk
  (`storage/app/private/cvs/`).
- **Soft deletes** — employers "delete" a listing but keep it (with its applications) visible
  in `/my-jobs` via `withTrashed()`; applicants see withdrawn listings in their history too.
- **Local-only** — no CI/CD, no deploy target; `just start` serves on port 8108.

## What it is not

- Not deployed anywhere; there is no production environment.
- No self-service user registration — sign in with a seeded account
  (`akmal@gmail.com` / `password` after `just fresh`).
- No email, queues-in-use, or file download UI for uploaded CVs.
- Test coverage is the stock Laravel example tests only (and see the known
  [`ExampleTest` failure](../06-troubleshooting/common-issues.md)).

## About the framework

The app is built on the Laravel framework (MIT-licensed skeleton). Framework-level learning
resources: [laravel.com/docs](https://laravel.com/docs), the
[Laravel Bootcamp](https://bootcamp.laravel.com), and [Laracasts](https://laracasts.com).

## Related docs

| Doc | Why |
| --- | --- |
| [architecture.md](architecture.md) | How the pieces fit together (routes → policies → controllers → scopes → views) |
| [../02-setup/getting-started.md](../02-setup/getting-started.md) | Get it running from a fresh PC |
| [../05-reference/project-layout.md](../05-reference/project-layout.md) | Where every file lives |
