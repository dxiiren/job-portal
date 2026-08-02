# CLAUDE.md — job-portal

> Human-facing developer docs live in [`.docs/`](./.docs/README.md) — start at
> [`.docs/tldr.md`](./.docs/tldr.md). Keep them in sync when changing behavior they document.

## Project: Job Portal

A Laravel 12 job-board application: browse and filter a seeded catalog of job listings
(search, salary range, experience level, category), sign in and apply to a job with an
expected salary plus a PDF CV, or register as an employer to post and manage your own
listings. Authorization is policy-driven — employers own their jobs, a job with
applications can't be edited, and each user can apply to a job only once.

- **Repo:** GitHub — `github.com/dxiiren/job-portal`
- **Runs locally only** — no CI/CD, no deployment target. `just start` serves on
  `http://127.0.0.1:8108`.

### Tech Stack Quick Reference

| Layer | Technology | Key details |
| --- | --- | --- |
| Framework | **Laravel 12** (PHP ^8.2, local PHP 8.4) | Routes in `routes/web.php`; resource controllers (`jobs`, `auth`, `job.application`, `my-job-applications`, `employer`, `my-jobs`) |
| ORM | Eloquent | `Job` (table **`offered_jobs`**, SoftDeletes, `scopeFilter` for search/salary/experience/category), `Employer`, `JobApplication`, `User` (hasOne employer) |
| Database | **SQLite** (`database/database.sqlite`, git-ignored) | `DB_CONNECTION=sqlite` from `.env.example`; sessions/cache/queue all in DB tables |
| Auth | Session login (`AuthController` + `LoginRequest::attempt`) | No registration UI — users come from the seeder (`akmal@gmail.com` / `password` seeker, `employer@gmail.com` / `password` employer); logout is a `DELETE` |
| Authorization | Policies + middleware | `JobPolicy` / `EmployerPolicy` / `JobApplicationPolicy` registered in `AppServiceProvider`; `can:` controller middleware (incl. `can:delete` on both destroy actions); `EmployerMiddleware` gates `/my-jobs` |
| Validation | FormRequest | `JobRequest` (enum-backed experience/category), `StoreJobApplicationRequest` (`cv` = `file\|mimes:pdf\|max:2048`), `StoreEmployerRequest`, `LoginRequest` |
| Uploads | Private `local` disk | CVs stored via `store('cvs', 'local')` → `storage/app/private/cvs/` (never public) |
| Views | Blade components | `<x-layout>` shell + `<x-job-card>`, `<x-breadcrumbs>`, `<x-text-input>`, `<x-radio-group>`, `<x-tag>`, `<x-card>` |
| Assets | Vite 6 + Tailwind CSS 4 + Alpine.js (npm) | `@vite` loads only when `public/build/manifest.json` exists (inline fallback otherwise) — `just bootstrap` builds once |
| Tests | PHPUnit 11 via `php artisan test` | Green feature suite of 74 tests (smoke, auth, the three policies, ownership on both delete routes, soft deletes, employer middleware, `scopeFilter`, `JobRequest` validation, CV/salary validation, seeder); `phpunit.xml` overrides DB to sqlite `:memory:` — tests never touch the dev db |
| Style | Laravel Pint | `just lint` / `just lint-fix` |
| Task runner | `just` | wraps php/composer/npm (`justfile`); PHP pinned to `%LOCALAPPDATA%\Programs\php-8.4` |

### Project Structure

```
job-portal/
  app/
    Enums/                  # JobCategoryEnum (IT/Finance/Sales/Marketing), JobExperienceEnum (entry/intermediate/senior)
    Http/Controllers/       # Job, JobApplication, MyJob, MyJobApplication, Employer, Auth
    Http/Middleware/        # EmployerMiddleware (redirects non-employers off /my-jobs)
    Http/Requests/          # JobRequest, StoreJobApplicationRequest, StoreEmployerRequest, LoginRequest
    Models/                 # Job (offered_jobs, SoftDeletes), Employer, JobApplication, User
    Policies/               # JobPolicy (applyJob, deny update once applications exist), EmployerPolicy, JobApplicationPolicy (applicant-only delete)
    Providers/              # AppServiceProvider (registers all three policies)
    View/Components/        # Breadcrumbs, Label, RadioGroup, TextInput
  bootstrap/, config/       # stock Laravel 12 config (cache/session/queue on database)
  database/
    migrations/             # users/cache/jobs (stock) + offered_jobs + job_applications + employers
    factories/, seeders/    # Job/Employer/JobApplication/User factories, DatabaseSeeder
  resources/
    views/                  # job/{index,show}, my_job/*, job_application/create, my_job_application/index, employer/create, auth/login, components/
    css/, js/               # Vite inputs (Tailwind 4 entry, Alpine.js boot)
  routes/web.php            # public jobs index/show + auth group (applications, employer, my-jobs)
  tests/                    # Feature tests: smoke, policies, CV validation, seeder (sqlite :memory:)
  justfile, setup.ps1       # dev recipes + one-time machine setup
  .docs/                    # numbered documentation set
  .claude/                  # skills, hooks, settings
```

## Git Commits

- **Conventional Commits** (`feat:`, `fix:`, `chore:`, `docs:` ...).
- **NEVER** add `Co-Authored-By` lines or "Generated with Claude Code" / session-link footers to
  **any** outward artifact — commit messages, PR descriptions, or issue comments.
- Commit author email for this repo is `mohdakmal875@gmail.com` (set repo-locally).
- Only stage and commit files relevant to the change. **Never auto-commit** after a fix — the
  developer says "commit" first.

## Local Development

- One-time machine setup: `pwsh ./setup.ps1` (idempotent — installs Git, Node.js, PHP 8.4 +
  Composer, uv/Python, just, the Claude Code CLI). Then `just bootstrap` (composer + npm +
  `.env` + sqlite + migrate + asset build), then `just start`.
- All day-2 commands are `just` recipes — run `just` to list them. Never invent an alternative
  command for something a recipe already covers.
- `just stop` kills only THIS repo's server processes (matched by repo path on the command
  line) — safe to run while other projects are serving.
- The database starts **empty** — run `just fresh` once to seed 300+ users, 21 employers,
  103 jobs and random applications. Deterministic logins: `akmal@gmail.com` / `password`
  (job seeker) and `employer@gmail.com` / `password` (employer). `just fresh`
  DROPS all local data; never run it to "fix" something without asking.
- The `Job` model maps to the **`offered_jobs`** table — the `jobs` table is Laravel's stock
  queue table. Mind this in raw SQL and new foreign keys.
- `just test` is green out of the box (74 tests) and runs on sqlite `:memory:` — keep it
  green; gate every change with it.
- Never edit committed migrations, `config/database.php`, or `.env.example` defaults.

## Project Skills

Development skills live in `.claude/skills/` — check `.claude/skills/README.md` for the catalog
and **follow the relevant skill before writing code**. Notables: `/commit`, `/create-pr`,
`/pre-pr-review`, `/lint-check`, `/claude-transfer`, `/llm-transfer`, `/define-goal`,
`/setup-mcp`, `/test-all-mcp`, `/audit-skills`.

## MCP Servers

Wired via the committed-stub + git-ignored-secret pattern: `.mcp.json.stub` (committed,
placeholders) → `.mcp.json` (git-ignored, real — seeded by `setup.ps1`). Turnkey: `context7`
(library docs — call `resolve-library-id` then `query-docs` instead of recalling APIs),
`playwright` (drive a real browser). Per-dev: `github` (fill the PAT in `.mcp.json`).
Health check: `/test-all-mcp`. Fall back to native tools silently if a server is unavailable.

## Memory

Lightweight, single-developer, file-based project memory at `.claude/memory/`:

- **`MEMORY.md`** is the index (one line per memory: `- [Title](file.md) — hook`), loaded each
  session.
- Each memory is **one fact in its own `*.md` file** with frontmatter (`name`, `description`,
  `metadata.type` = `reference` | `feedback` | `project`). Read the fact file on demand when its
  index hook is relevant.
- After writing a fact file, add its one-line pointer to `MEMORY.md`. Update rather than
  duplicate; delete a memory that turns out wrong. Don't store what the repo already records.
