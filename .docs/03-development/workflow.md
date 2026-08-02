# Development workflow

> **TL;DR** Branch off `main`, `just serve` while editing, follow the house patterns
> (FormRequests, policies, model scopes, Blade components), gate with `just lint` +
> `just test` (there is no CI), commit Conventional-Commits style, PR via `gh`.

## The day-2 loop

1. `git checkout -b feat/...` off `main`.
2. `just serve` (foreground) — PHP's dev server picks up code changes per request, no
   restarts needed. Rebuild assets only when you touch `resources/css|js` (`npm run build`,
   or `npm run dev` for HMR while styling).
3. Edit, refresh, repeat.
4. Gate before committing: `just lint` + `just test`. **This is the only quality gate — no CI.**
5. `/commit` (or hand-written Conventional Commits), `/create-pr` for a PR into `main`.

## House patterns (follow these, don't invent new ones)

| Change | Pattern |
| --- | --- |
| New query/filter on listings | Extend `Job::scopeFilter` (chained `when()`), not inline controller queries |
| New write endpoint | FormRequest for validation + a policy method + `can:` middleware on the controller |
| New user-type restriction | Policy first; a route-level middleware (like `EmployerMiddleware`) only for redirects |
| New form field | Add to the FormRequest `rules()` AND the model `$fillable` AND a migration (new file) |
| New page | Blade view wrapped in `<x-layout>`; reuse `<x-job-card>`, `<x-text-input>`, `<x-radio-group>`, `<x-breadcrumbs>` |
| New enum-ish column | PHP enum in `app/Enums/` with a `values()` helper + `new Enum(...)` validation rule |
| Test data | Factories (`JobFactory`, `EmployerFactory`, `JobApplicationFactory`) — never hand-inserted rows |

## Sharp edges

- **`Job` ↔ `offered_jobs`.** The `jobs` table belongs to Laravel's queue. Raw SQL, new
  foreign keys, and `constrained()` guesses must target `offered_jobs` (the model's
  `$table` handles this when you stay in Eloquent).
- **Never edit a committed migration** — add a new one. Local resets are `just fresh`.
- **`just fresh` wipes local data** — applications you created by hand are gone. Don't run
  it to "fix" an unrelated problem.
- **Salary validation is length-based** — `expected_salary` (`StoreJobApplicationRequest`)
  has `min:1|max:1000000` without `numeric`, so min/max validate string LENGTH. Known quirk;
  fix deliberately, not as a drive-by.
- **The suite is green — keep it green** — `just test` runs real feature tests (smoke,
  policies, CV validation, seeder demo accounts). A clean clone passes; a red suite means
  your change broke something.
- **Tests run on sqlite `:memory:`** — `phpunit.xml` overrides `DB_CONNECTION`/`DB_DATABASE`,
  so `RefreshDatabase` tests never touch the seeded dev `database/database.sqlite`.
- **CV files are private** — keep `store('cvs', 'local')`; anything public leaks PDFs.

## Working with Claude Code

- `just claudex` launches Claude Code (Sonnet) with permissions pre-granted.
- Project skills live in `.claude/skills/` — `/commit`, `/create-pr`, `/pre-pr-review`,
  `/lint-check`, `/define-goal`, `/claude-transfer`, `/llm-transfer`, `/setup-mcp`,
  `/test-all-mcp`, `/audit-skills`. Follow the relevant skill before writing code.
- MCP servers (context7, playwright, github) are wired via `.mcp.json.stub` → `.mcp.json`;
  run `/test-all-mcp` to smoke-test them.

## Related docs

| Doc | Why |
| --- | --- |
| [../01-overview/architecture.md](../01-overview/architecture.md) | The patterns this workflow references |
| [../05-reference/commands.md](../05-reference/commands.md) | Recipe reference |
| [../06-troubleshooting/common-issues.md](../06-troubleshooting/common-issues.md) | When something breaks mid-loop |
