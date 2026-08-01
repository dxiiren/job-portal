# Common issues

> **TL;DR** Real symptom → cause → fix, from actual verification runs on this repo. Check
> here before debugging from scratch. The two "expected failures" on a clean clone are
> `just test` (stock `ExampleTest` vs the `/` redirect) and `just lint` (pre-existing Pint
> debt) — neither means your setup is broken.

## `/jobs` shows an empty list

**Symptom** — the job list renders but has no job cards; signing in is impossible.

**Cause** — `just bootstrap` migrates but does not seed; the database starts empty, and
there is no registration UI to create a user.

**Fix** — `just fresh` (seeds 301 users, 20 employers, 100 jobs, random applications; the
known login is `akmal@gmail.com` / `password`). Note it DROPS existing local data.

## `just test` fails out of the box

**Symptom** —

```
FAILED  Tests\Feature\ExampleTest > the application returns a successful response
Expected response status code [200] but received 302.
Tests:    1 failed, 1 passed (2 assertions)
```

**Cause** — pre-existing: the stock `tests/Feature/ExampleTest.php` asserts `GET /` returns
200, but `routes/web.php` redirects `/` to `jobs.index` (302). Not a setup problem.

**Fix** — none required for onboarding. If you want green, change the assertion to
`assertRedirect(route('jobs.index'))` in a dedicated `test:` commit — never as a side
effect of unrelated work. Meanwhile, gate your own changes with
`just test --filter=YourNewTest`.

## `just lint` fails with style violations you didn't write

**Symptom** — Pint exits 1 listing ~20 files (`app/Models/User.php`, both policies, the
View components, all three 2025 migrations, `DatabaseSeeder`, `routes/web.php`, ...) with
`ordered_imports`, `single_blank_line_at_eof`, `braces_position`, `yoda_style`, etc.

**Cause** — pre-existing style debt in the app source; Pint has simply never been run here.

**Fix** — check only your files with
`& "$env:LOCALAPPDATA\Programs\php-8.4\php.exe" vendor\bin\pint --test <path>`, or clean
the whole repo once with `just lint-fix` in a dedicated `style:` commit.

## `PHP 8.4 not found at ...` when running a recipe

**Symptom** — any recipe fails immediately with
`PHP 8.4 not found at C:\Users\<you>\AppData\Local\Programs\php-8.4\php.exe`.

**Cause** — `setup.ps1` hasn't run on this machine (or was interrupted before the PHP step).

**Fix** — `pwsh ./setup.ps1`, close and reopen PowerShell, retry. The recipes call PHP by
absolute path, so a stale PATH alone won't cause this — a missing install will.

## Pages render unstyled / plain

**Symptom** — the job list loads but looks like unstyled HTML with a stock font.

**Cause** — the `<x-layout>` component loads the Vite bundle only when
`public/build/manifest.json` exists; otherwise it falls back to an inline stylesheet that
does not include this app's utility classes. You skipped `npm run build` (it's part of
`just bootstrap`).

**Fix** — `npm run build` once, or `npm run dev` while actively styling. Re-run
`just bootstrap` if in doubt — it's idempotent.

## Port 8108 already in use

**Symptom** — `just start` window closes instantly, or `curl` hits a different app.

**Cause** — a lingering serve process, or another program on the port.

**Fix** — `just stop` (kills only this repo's `php.exe` — it matching by the repo path in
the command line; seeing it report 2+ processes is normal, PHP's dev server runs
`PHP_CLI_SERVER_WORKERS=4` workers). If the port is held by something else:
`netstat -ano | findstr :8108`. One-off alternative port: `$env:PORT=8200; just start`.

## A feature test wiped my seeded data

**Symptom** — after running tests, `/jobs` is empty again.

**Cause** — this repo's `phpunit.xml` has the sqlite/`:memory:` DB overrides **commented
out**, so tests run against the dev `database/database.sqlite`. Any test using
`RefreshDatabase` (none do today, but the import is stubbed in `ExampleTest`) will
drop-and-remigrate your dev data.

**Fix** — re-seed with `just fresh`. Before writing tests that touch the DB, uncomment the
two `DB_CONNECTION`/`DB_DATABASE` lines in `phpunit.xml` in a dedicated commit so tests use
an in-memory database.

## `setup.ps1` seems to hang

**Symptom** — the script stops mid-run with no output (historically at the VC++ step).

**Cause** — winget attempting an in-place upgrade of an already-present package can throw a
UAC prompt that unattended shells never see. The VC++ step in this repo's `setup.ps1`
already registry-checks first (the known fix), so a hang there means a different package
triggered UAC.

**Fix** — re-run in an interactive elevated PowerShell once; subsequent runs are no-ops.

## Related docs

| Doc | Why |
| --- | --- |
| [../02-setup/getting-started.md](../02-setup/getting-started.md) | The happy-path these issues deviate from |
| [../05-reference/commands.md](../05-reference/commands.md) | What each recipe mentioned here actually runs |
| [../07-faq/faq.md](../07-faq/faq.md) | Questions that aren't failures |
