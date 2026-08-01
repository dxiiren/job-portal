# Getting started

> **TL;DR** `pwsh ./setup.ps1` → reopen PowerShell → `just bootstrap` → `just fresh`
> (seed) → `just start` → open http://127.0.0.1:8108 and sign in with
> `akmal@gmail.com` / `password`. Everything is idempotent and safe to re-run.

## Prerequisites

A stock Windows 10/11 machine with PowerShell and winget. Everything else is installed by
`setup.ps1` — see the table in the [root README](../../README.md#prerequisites).

## Step 1 — Machine setup (once per machine)

```powershell
pwsh ./setup.ps1
```

Installs (or verifies, on re-run): Git, Node.js LTS, the Claude Code CLI, uv + Python, the
VC++ 2015-2022 redistributable (registry-checked first so re-runs don't trigger UAC), PHP
8.4 (php.net zip → `%LOCALAPPDATA%\Programs\php-8.4`, with a `php.ini` carrying the
Laravel-required extensions), Composer, just, and the GitHub CLI. It also seeds `.mcp.json`
from `.mcp.json.stub` for Claude Code MCP servers.

Every step prints `[OK]` / `[INSTALL]` / `[WARN]` / `[FAIL]`. A fully-green re-run is the
expected steady state.

## Step 2 — Reopen PowerShell

PATH changes (PHP, Composer, just, uv) only land in new shells. Close and reopen PowerShell.

## Step 3 — App bootstrap (once per clone)

```powershell
just bootstrap
```

Creates `.env` from `.env.example` (already sqlite-flavoured) and an empty
`database/database.sqlite`, then `composer install`, `npm install`, `npm run build` (Vite —
without this, pages render on a fallback stylesheet), `artisan key:generate`, and
`artisan migrate`.

## Step 4 — Seed (recommended)

```powershell
just fresh
```

Drops and re-migrates the database, then seeds 301 users, 20 employers, 100 jobs, and
random applications. Without seeding, `/jobs` is an empty list and there is no account to
sign in with. **Destroys existing local data** — that's fine on a fresh clone.

## Step 5 — Run

```powershell
just start        # background window; `just serve` for foreground
```

Open **http://127.0.0.1:8108** — you land on the job list. Sign in as
`akmal@gmail.com` / `password` to apply for jobs; visit `/my-jobs` and register a company
to try the employer side. Stop with `just stop` (kills only this repo's `php.exe`).

## Verify your setup

| Check | Expect |
| --- | --- |
| `curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8108/` | `302` (redirect to `/jobs`) |
| `curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8108/jobs` | `200` |
| `/jobs` in a browser after `just fresh` | Paginated job cards with filters |
| `just lint` | Pint runs (see [common-issues](../06-troubleshooting/common-issues.md) for pre-existing debt) |

## Related docs

| Doc | Why |
| --- | --- |
| [../03-development/workflow.md](../03-development/workflow.md) | What day-2 development looks like |
| [../05-reference/commands.md](../05-reference/commands.md) | Every `just` recipe |
| [../06-troubleshooting/common-issues.md](../06-troubleshooting/common-issues.md) | When a step above fails |
