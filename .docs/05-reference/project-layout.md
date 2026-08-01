# Project layout

> **TL;DR** A standard Laravel 12 skeleton carrying ~25 domain PHP files (6 controllers,
> 4 models, 2 policies, 4 FormRequests, 2 enums, 1 middleware), 18 Blade views/components,
> factories + a seeder, and the onboarding kit (`justfile`, `setup.ps1`, `.docs/`,
> `.claude/`).

## Annotated tree

```
job-portal/
  app/
    Enums/
      JobCategoryEnum.php          # IT / Finance / Sales / Marketing (+ values() helper)
      JobExperienceEnum.php        # entry / intermediate / senior (+ values() helper)
    Http/
      Controllers/
        JobController.php          # public list (filter scope + paginate) + detail
        JobApplicationController.php  # apply form + store (CV upload, can:applyJob)
        MyJobApplicationController.php # applicant's list + withdraw
        MyJobController.php        # employer CRUD on own jobs (can:* per action)
        EmployerController.php     # become-an-employer form + store
        AuthController.php         # login form, attempt, logout
      Middleware/
        EmployerMiddleware.php     # /my-jobs gate: no employer -> redirect employer.create
      Requests/
        JobRequest.php             # title/location/salary/description + enum rules
        StoreJobApplicationRequest.php # expected_salary + cv (file|mimes:pdf|max:2048)
        StoreEmployerRequest.php   # company_name
        LoginRequest.php           # email/password + attempt() helper
    Models/
      Job.php                      # table offered_jobs, SoftDeletes, scopeFilter, hasUserApplied
      Employer.php                 # company_name; belongsTo User, hasMany Job
      JobApplication.php           # belongsTo Job + User
      User.php                     # hasOne Employer, hasMany JobApplication
    Policies/
      JobPolicy.php                # create/update/delete/applyJob rules
      EmployerPolicy.php           # one employer account per user
    Providers/
      AppServiceProvider.php       # Gate::policy registrations
    View/Components/               # Breadcrumbs, Label, RadioGroup, TextInput (class-backed)
  bootstrap/
    app.php                        # Laravel 12 app config (routing, middleware alias)
    providers.php
  config/                          # stock: cache/session/queue all on the database driver
  database/
    database.sqlite                # local db (git-ignored; created by just bootstrap)
    migrations/
      0001_01_01_*                 # stock users / cache / jobs (queue) tables
      2025_03_28_033451_create_jobs_table.php         # offered_jobs (+ experience DB enum)
      2025_03_28_033609_create_job_applications_table.php
      2025_03_28_033635_create_employers_table.php    # employers + employer_id on offered_jobs
    factories/                     # User, Employer, Job, JobApplication
    seeders/DatabaseSeeder.php     # 301 users, 20 employers, 100 jobs, random applications
  public/                          # web root; build/ appears after npm run build (git-ignored)
  resources/
    css/app.css                    # Tailwind 4 entry (@import 'tailwindcss' + @source globs)
    js/app.js                      # boots Alpine.js
    views/
      components/                  # layout (the <x-layout> shell), job-card, card, tag,
                                   # text-input, radio-group, label, button, link-button, breadcrumbs
      job/{index,show}.blade.php   # public listing + detail
      job_application/create.blade.php
      my_job/{index,create,edit}.blade.php
      my_job_application/index.blade.php
      employer/create.blade.php
      auth/login.blade.php
      welcome.blade.php            # stock Laravel welcome (unused; / redirects to /jobs)
  routes/web.php                   # the whole route table
  storage/app/private/cvs/         # uploaded CVs land here (runtime, git-ignored)
  tests/                           # stock Feature/Unit example tests
  justfile                         # dev recipes (see 05-reference/commands.md)
  setup.ps1                        # one-time machine setup
  .docs/                           # this documentation set
  .claude/                         # Claude Code skills, hooks, settings, memory
  .mcp.json.stub                   # committed MCP config template (-> git-ignored .mcp.json)
  CLAUDE.md                        # AI-assistant briefing for this repo
```

## Where to make which change

| You want to... | Touch |
| --- | --- |
| Add a listing filter | `Job::scopeFilter` + the filter form in `resources/views/job/index.blade.php` |
| Add a field to jobs | New migration (offered_jobs) + `Job::$fillable` + `JobRequest::rules` + the my_job forms |
| Change application rules | `StoreJobApplicationRequest` (validation) / `JobPolicy::applyJob` (eligibility) |
| Change who can do what | `JobPolicy` / `EmployerPolicy` (+ `can:` middleware in the controller) |
| Restyle a page | The page's Blade view / shared pieces in `resources/views/components/` (then `npm run build`) |
| Add a page | Route in `web.php` → controller → Blade view wrapped in `<x-layout>` |
| Change seed data shape | `database/seeders/DatabaseSeeder.php` + the relevant factory |

## Related docs

| Doc | Why |
| --- | --- |
| [../01-overview/architecture.md](../01-overview/architecture.md) | How these files interact at runtime |
| [commands.md](commands.md) | The recipes that build/run all of this |
| [../03-development/workflow.md](../03-development/workflow.md) | The patterns to follow when editing |
