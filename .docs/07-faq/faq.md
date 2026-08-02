# FAQ

> **TL;DR** Quick answers on ports, login, seeding, the two-sided job/employer model,
> uploads, soft deletes, tests, and deployment. If your question is an error message, go to
> [common-issues](../06-troubleshooting/common-issues.md) instead.

### What port does the app run on?

8108 (`http://127.0.0.1:8108`), set in the `justfile`. One-off override:
`$env:PORT=8200; just start`. Don't hardcode other ports — each repo on this machine has
an assigned one.

### How do I log in? There's no register page.

There is no self-service registration. Seed first (`just fresh`), then sign in as
`akmal@gmail.com` / `password` — or any of the 300 random users via
`artisan tinker` (`User::inRandomOrder()->first()->email`; every seeded password is
`password`).

### How do I try the employer side?

Sign in, open `/my-jobs` — `EmployerMiddleware` redirects you to the become-an-employer
form. Register a company name once, then `/my-jobs` gives you the full CRUD. A user can
have exactly one employer account (`EmployerPolicy::create`).

### Why can't I edit this job?

`JobPolicy::update` denies editing once the job has applications ("Cannot change the job
with applications"), and you can only ever edit your own jobs. Deleting is allowed — it's a
soft delete, and the job stays visible (with applications) in `/my-jobs`.

### Why can't I apply to a job twice?

`JobPolicy::applyJob` → `Job::hasUserApplied` blocks a second application. Withdraw the
first one from `/my-job-applications` if you need to re-apply while testing.

### Where do uploaded CVs go?

`storage/app/private/cvs/` — the private `local` disk (`store('cvs', 'local')` in
`JobApplicationController`). They're git-ignored, only PDFs up to 2 MB pass validation, and
there is no download UI yet.

### Why is the table called `offered_jobs` and not `jobs`?

Stock Laravel already creates a `jobs` table for the database queue driver. The `Job` model
sets `$table = 'offered_jobs'`. Stay in Eloquent and you'll never notice; raw SQL must use
`offered_jobs`.

### Where does the job list data come from?

`DatabaseSeeder` + factories: 301 users, 20 employers, 100 jobs, 0–4 applications per
user. `just fresh` re-rolls everything (destructive); `artisan db:seed` adds another batch
on top without dropping.

### `just start` vs `just serve`?

`start` = background window, terminal stays free, `just stop` to end. `serve` = foreground
with the request log, Ctrl+C to end. Same server either way.

### Why does `just stop` say it stopped 2 (or more) processes?

PHP's dev server forks workers (`PHP_CLI_SERVER_WORKERS=4` in `.env`). All of them carry
this repo's path on their command line, so `stop` finds and kills the whole family — and
never touches another project's `php.exe`.

### Do `just test` and `just lint` pass on a clean clone?

Yes, both. They used to fail (a stock `ExampleTest` asserted 200 on `/`, which is a 302
redirect, and Pint had never been run on the app source); the example test was replaced by
a real suite and Pint was applied repo-wide. Keep both green — they are the whole quality
gate, there is no CI.

### What are the filter options on `/jobs`?

Free-text search (job title, description, or employer company name), min/max salary,
experience (`entry` / `intermediate` / `senior`), category (`IT` / `Finance` / `Sales` /
`Marketing`). All composable — they chain in `Job::scopeFilter` — and pagination keeps them
via `appends()`.

### Is this deployed anywhere?

No. No CI/CD, no hosting, local only. [deployment.md](../04-deployment/deployment.md)
records what a real deploy would need.

## Related docs

| Doc | Why |
| --- | --- |
| [../01-overview/project-overview.md](../01-overview/project-overview.md) | The longer-form what-and-why |
| [../06-troubleshooting/common-issues.md](../06-troubleshooting/common-issues.md) | Error-shaped questions |
| [../05-reference/commands.md](../05-reference/commands.md) | Every command referenced above |
