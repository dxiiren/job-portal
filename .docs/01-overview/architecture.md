# Architecture

> **TL;DR** Server-rendered MVC: `routes/web.php` → six resource controllers guarded by
> policies + middleware → Eloquent models (`Job` on `offered_jobs` with a composable
> `filter` scope and soft deletes) → Blade component views built by Vite/Tailwind 4.
> SQLite holds everything, including sessions and cache.

## Request flow

```
Browser
  → routes/web.php            (public: jobs index/show, auth; auth group: applications, employer, my-jobs)
  → middleware                 (auth group · EmployerMiddleware on /my-jobs · can:* from controllers)
  → Controller                 (Job, JobApplication, MyJob, MyJobApplication, Employer, Auth)
  → FormRequest validation     (JobRequest, StoreJobApplicationRequest, StoreEmployerRequest, LoginRequest)
  → Eloquent model             (Job, Employer, JobApplication, User)
  → Blade view                 (<x-layout> shell + components)
```

## Route map

| Route(s) | Controller | Guard |
| --- | --- | --- |
| `/` | closure → redirect `jobs.index` | public |
| `/jobs`, `/jobs/{job}` | `JobController@index/show` | public |
| `/auth/create`, `POST /auth`, `DELETE /auth/{auth}`, `/login`, `DELETE /logout` | `AuthController` | public |
| `/job/{job}/application/create`, `POST .../application` | `JobApplicationController` | `auth` + `can:applyJob,job` |
| `/my-job-applications` (index, destroy) | `MyJobApplicationController` | `auth` |
| `/employer/create`, `POST /employer` | `EmployerController` | `auth` + `can:create,Employer` |
| `/my-jobs` (full resource) | `MyJobController` | `auth` + `EmployerMiddleware` + `can:*` per action |

## Data model

```
users 1──1 employers 1──* offered_jobs 1──* job_applications *──1 users
```

| Table | Model | Notes |
| --- | --- | --- |
| `users` | `User` | stock auth columns; `hasOne(Employer)`, `hasMany(JobApplication)` |
| `employers` | `Employer` | `company_name` + `user_id`; created once per user (`EmployerPolicy::create`) |
| `offered_jobs` | `Job` | title, description, salary (unsigned int), location, category (string), experience (**DB enum** of `JobExperienceEnum` values), `softDeletes`; `employer_id` added by the employers migration |
| `job_applications` | `JobApplication` | `user_id` + `job_id` (cascade on delete), `expected_salary`, `cv_path` |
| `cache`, `jobs`, `sessions` | — | stock Laravel infrastructure tables (database cache/queue/session stores) |

Enums live in `app/Enums/`: `JobCategoryEnum` (IT, Finance, Sales, Marketing) and
`JobExperienceEnum` (entry, intermediate, senior). `JobRequest` validates against them with
`new Enum(...)`; the jobs-table migration bakes the experience values into a DB-level enum.

## Authorization

| Rule | Enforced by |
| --- | --- |
| Only signed-in users apply / manage applications / become employers | `auth` middleware group in `routes/web.php` |
| `/my-jobs` requires an employer account (else redirect to `employer.create`) | `EmployerMiddleware` |
| One employer account per user | `EmployerPolicy::create` (`can:create,App\Models\Employer`) |
| Create a job requires an employer account | `JobPolicy::create` |
| Edit/delete a job requires ownership | `JobPolicy::update/delete` |
| A job with applications cannot be edited | `JobPolicy::update` → `Response::deny` |
| One application per user per job | `JobPolicy::applyJob` → `Job::hasUserApplied` |

Both policies are registered explicitly in `AppServiceProvider::boot` with `Gate::policy`.

## Query patterns

- **List page** — `Job::with('employer')->latest()->filter($filters)->paginate()->appends(...)`;
  `scopeFilter` chains `when()` for `search` (title / description / `orWhereRelation` on
  employer company name), `min_salary`, `max_salary`, `experience`, `category`.
- **My applications** — eager-loads `job` with `withCount('jobApplications')` and
  `withAvg('jobApplications', 'expected_salary')` plus `withTrashed()`, so applicants see
  stats and withdrawn listings without N+1 queries.
- **My jobs** — the employer's own jobs `withTrashed()`, eager-loading applications and their
  users.

## The write paths

| Write | Flow |
| --- | --- |
| Sign in | `LoginRequest::attempt` (`auth()->attempt`, optional remember) |
| Apply | `StoreJobApplicationRequest` → `$request->file('cv')->store('cvs', 'local')` (private disk) → create with `expected_salary` + `cv_path` |
| Become employer | `StoreEmployerRequest` → `user->employer()->create` |
| Post / edit job | `JobRequest` (enum-validated) → `employer->jobs()->create` / `job->update` |
| Withdraw application / delete job | `destroy` actions; job delete is a **soft** delete |

## Views

Blade components, no page controller logic: `components/layout.blade.php` is the shell
(`<x-layout>` — nav, session success/error flashes), pages compose `<x-job-card>`,
`<x-breadcrumbs>`, `<x-text-input>`, `<x-radio-group>`, `<x-tag>`, `<x-card>`,
`<x-link-button>`. Class-backed components (`Breadcrumbs`, `Label`, `RadioGroup`,
`TextInput`) live in `app/View/Components/`.

Assets: Vite 6 builds `resources/css/app.css` (Tailwind 4 via `@tailwindcss/vite`) and
`resources/js/app.js` (boots Alpine.js). The layout loads the build only when
`public/build/manifest.json` exists and falls back to an inline stylesheet otherwise — run
`npm run build` (part of `just bootstrap`) or pages render on the fallback CSS, which does
not cover the app's utility classes.

## Seeding

`DatabaseSeeder`: 1 known user (`akmal@gmail.com` / `password`) + 300 random users →
20 employers (random users) → 100 jobs (random employers) → 0–4 applications per remaining
user. Factories: `UserFactory`, `EmployerFactory`, `JobFactory`, `JobApplicationFactory`.

## Related docs

| Doc | Why |
| --- | --- |
| [project-overview.md](project-overview.md) | What the app is, feature by feature |
| [../03-development/workflow.md](../03-development/workflow.md) | House patterns to follow when changing this |
| [../05-reference/project-layout.md](../05-reference/project-layout.md) | Annotated file tree |
