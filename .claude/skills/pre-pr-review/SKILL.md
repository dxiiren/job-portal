---
name: pre-pr-review
description: Use when the developer says 'pre-pr review', 'review my branch', 'audit my work', or 'self review' — self-reviews the current branch's diff against a Laravel / Eloquent / Blade / security checklist before opening a PR, then saves a report to .claude/workspace/reports/pr/.
model: opus
---

# Pre-PR Review (Self-Audit)

Self-review your feature-branch diff **before** opening a PR. This is a single-stack
Laravel 12 / Blade app — no SPA, no separate API track. The goal is to catch query,
validation, authorization, and security problems early, not to nitpick style Pint already
handles.

## Trigger

- `"pre-pr review"` / `"self review"`
- `"review my branch"` / `"review my work"` / `"review my code"`
- `"audit my work"` / `"audit my branch"`

## Do NOT flag (owned elsewhere)

- **Formatting / code style** — Laravel Pint owns it (`just lint`). Run it; don't hand-review it.
- **Pre-existing patterns** the developer copied from the codebase — not this branch's problem.

## Step 1 — Branch & base

```bash
git branch --show-current
```

If on `main`: **STOP** — "You're on `main`; switch to your feature branch first."

```bash
git fetch origin main
git diff origin/main...HEAD --name-only
```

If no files changed: **STOP** — "No changes vs `main`."

Scope the review to reviewable source: `app/**/*.php`, `routes/*.php`,
`database/migrations/`, `database/factories/`, `database/seeders/`,
`resources/views/**/*.blade.php`, `config/*.php`. **Exclude** `composer.lock`,
`package-lock.json`, and `.claude/`. If only excluded files changed: **STOP** —
"No reviewable source changed."

Report: "Branch `{name}` changed {N} source files ({php} .php, {blade} .blade.php). Running review."

## Step 2 — Fetch the diff

```bash
git diff origin/main...HEAD -- 'app' 'routes' 'database' 'resources/views' 'config'
```

For context-dependent checks (cache invalidation, scope correctness, route binding), read
the **full file**, not just the hunk. If the diff exceeds ~4000 lines, prioritise the
highest-change files and note "focused review on largest files".

## Step 3 — Run the checklist

Verify each finding against the actual code before reporting it (grep how existing code does
the same thing; don't invent a rule the codebase doesn't follow).

| #   | Check                       | Label      | What to look for                                                                                                                                                                                                                                                  |
| --- | --------------------------- | ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Validation**              | issue      | Controller reading `$request->input()` and persisting without a FormRequest (`JobRequest` / `StoreJobApplicationRequest` are the house pattern); missing `rules()` for a new field; a numeric field validated without `numeric`/`integer` (min/max then check string LENGTH — see `expected_salary`).       |
| 2   | **Mass assignment**         | issue      | New model attributes not in `$fillable`; `create($request->all())` instead of `create($request->validated())`.                                                                                                                                                     |
| 3   | **Query efficiency (N+1)**  | issue      | Iterating a relation in Blade without eager loading (`with`/`load`/`withCount`/`withAvg`); a query inside a `@foreach`; unbounded `->get()` on a list page that should paginate or limit.                                                                          |
| 4   | **Authorization (policies)**| issue      | New job/employer/application actions bypassing `JobPolicy`/`EmployerPolicy` (house pattern: `can:` middleware on controllers + `Gate::policy` in `AppServiceProvider`); an ownership check comparing the wrong id; soft-delete blind spots (`Job` uses `SoftDeletes` — decide `withTrashed()` explicitly).   |
| 5   | **Blade escaping (XSS)**    | issue      | `{!! !!}` on user-supplied content (job title/description and company name are user input — must stay `{{ }}`); `@php` blocks doing logic that belongs in the controller.                                                                                          |
| 6   | **Routing & binding**       | issue      | Nested resources not `scoped()` where a child must belong to its parent; a new route bypassing route-model binding to run manual `find()`; missing `only([...])` leaving unintended resource actions exposed.                                                       |
| 7   | **Uploads / abuse**         | issue      | A new write endpoint outside the `auth` middleware group without a deliberate reason; file uploads loosening the CV rules (`file|mimes:pdf|max:2048`) or storing to a public disk (CVs go to the private `local` disk via `store('cvs', 'local')`).                 |
| 8   | **Migrations**              | issue      | Editing an already-committed migration instead of adding a new one; missing `down()`; a foreign key without `constrained()` / an explicit cascade decision.                                                                                                        |
| 9   | **Secrets / config**        | issue      | Hardcoded credentials/API keys; reading `env()` outside `config/`; committing `.env` or `database/database.sqlite`.                                                                                                                                                |
| 10  | **Tests**                   | issue      | New/changed behavior with no feature test; a changed assertion watered down to pass.                                                                                                                                                                              |
| 11  | **No debug leftovers**      | issue      | `dd()` / `dump()` / `ray()` / `Log::debug` spam / commented-out dead blocks / `TODO` without a follow-up.                                                                                                                                                          |
| 12  | **Eloquent design**         | suggestion | Query logic inline in a controller that belongs in a model scope (the house pattern: `Job::scopeFilter` handling search/salary/experience/category); duplicated filter logic; a scope that silently changes global behavior.                                        |
| 13  | **Blade structure**         | suggestion | Repeated markup that should be a component (house patterns: `<x-job-card>`, `<x-text-input>`, `<x-radio-group>`, `<x-breadcrumbs>`); logic-heavy views; a new page not wrapped in `<x-layout>`.                                                                     |
| 14  | **Naming / conventions**    | nitpick    | Non-RESTful controller method names; a route name that breaks the `jobs.*` / `my-jobs.*` / `job.application.*` convention; migration filename not matching its table (note: model `Job` maps to the `offered_jobs` table — `jobs` is Laravel's queue table).        |

## Step 4 — Run the quality suite

```powershell
just lint
just test
```

Both must be green. A failure is an **issue** (blocking) — paste the failing output line.

## Step 5 — Finding labels & caps

- **issue** (blocking) — fix before opening the PR.
- **suggestion** (non-blocking) — recommended.
- **nitpick** (non-blocking) — minor/optional.

Every finding must carry: the label, the `file:line`, and **WHY** it matters (not just what).
Issues: uncapped. Suggestions + nitpicks: cap at 15 total; note "{X} more non-blocking findings
omitted" if over.

## Step 6 — Present

```
## Pre-PR Review: {branch}
Branch: {branch} -> main   |   Files: {N} ({php} .php, {blade} .blade.php)
Quality suite: {pint pass/fail} · {test pass/fail}

### Issues (fix before PR)
1. [path:line] Finding — why it matters

### Suggestions
2. [path:line] Finding

### Nitpicks
3. [path:line] Finding

---
{Total} findings: {issues} issues, {suggestions} suggestions, {nitpicks} nitpicks
```

Zero findings → "No issues found — branch looks clean. Ready to open the PR."

## Step 7 — Save the report

Path: `.claude/workspace/reports/pr/{branch}-{YYYY-MM-DD}.md` (replace `/` in the branch name
with `-`; overwrite on a same-day re-run; create the folder if missing). Frontmatter then the
same body as the terminal output:

```yaml
---
branch: { branch }
base: main
date: { YYYY-MM-DD }
files_changed: { N }
issues: { count }
suggestions: { count }
nitpicks: { count }
---
```

Confirm: "Report saved to `{path}`".

## Tone

Self-improvement, not a verdict from a lead. "Consider extracting…", not "You must fix…". Never
directive, never judgmental.
