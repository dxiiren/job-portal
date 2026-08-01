# Commands reference

> **TL;DR** Everything routine is a `just` recipe (run `just` to list them). PHP and
> Composer resolve by absolute path (`%LOCALAPPDATA%\Programs\php-8.4`), so recipes work
> even in shells opened before `setup.ps1` updated PATH.

## Setup

| Command | What it does | Re-runnable? |
| --- | --- | --- |
| `pwsh ./setup.ps1` | Install/verify the whole toolchain (Git, Node, PHP 8.4, Composer, uv, just, gh, Claude CLI) | Yes — idempotent, all-`[OK]` steady state |
| `just bootstrap` | `.env` + sqlite file + `composer install` + `npm install` + `npm run build` + `key:generate` + `migrate` | Yes — each step is a no-op when already done |

## App lifecycle

| Command | What it does |
| --- | --- |
| `just start` | Serve http://127.0.0.1:8108 in a background window (runs `stop` first, so no doubled servers) |
| `just serve` | Serve in the foreground — Ctrl+C to stop; request log visible |
| `just stop` | Kill only THIS repo's `php.exe` (matches the repo path in the process command line) |

Override the port for one run: `$env:PORT=8200; just start` (unset to return to 8108).

## Database

| Command | What it does |
| --- | --- |
| `just migrate` | Run pending migrations |
| `just fresh` | `migrate:fresh --seed` — DROP everything, re-migrate, seed 301 users / 20 employers / 100 jobs. **Irreversible locally** |

## Quality

| Command | What it does |
| --- | --- |
| `just test` | `php artisan test` (PHPUnit); pass flags through: `just test --filter=ExampleTest` |
| `just lint` | Pint style check, read-only (`vendor\bin\pint --test`) |
| `just lint-fix` | Pint auto-fix (writes changes) |

## Claude Code

| Command | What it does |
| --- | --- |
| `just claudex` | Claude Code, Sonnet, `--dangerously-skip-permissions` |
| `just claudeo` | Same, Opus |
| `just claudeh` | Same, Haiku |

## Raw artisan commands worth knowing

Recipes cover the routine; for the occasional one-off (always via the pinned PHP —
`& "$env:LOCALAPPDATA\Programs\php-8.4\php.exe" artisan ...`):

| Command | When |
| --- | --- |
| `artisan route:list` | See every route + middleware + name in one table |
| `artisan tinker` | Poke models interactively (`Job::first()`, `User::count()`) |
| `artisan migrate:status` | Which migrations have run |
| `artisan db:seed` | Re-seed WITHOUT dropping (adds another batch of rows) |
| `artisan cache:clear` / `view:clear` | Flush the database cache store / compiled Blade views |

## Related docs

| Doc | Why |
| --- | --- |
| [project-layout.md](project-layout.md) | Where the files these commands touch live |
| [../02-setup/getting-started.md](../02-setup/getting-started.md) | First-run order of these commands |
| [../06-troubleshooting/common-issues.md](../06-troubleshooting/common-issues.md) | When a command fails |
