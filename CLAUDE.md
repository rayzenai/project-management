# CLAUDE.md — `rayzenai/project-management`

Guidance for Claude Code working **inside this package**. This is the heart of the
workspace system: a Laravel library that ships a project/task tracking workspace
(projects → tasks → subtasks, assignments, notes, contacts, teams, members,
activity log, weekly digest) for a Laravel + Inertia host app. The repo-root
`/Users/kiran/rayzen/kiranwork/CLAUDE.md` and `/Users/kiran/rayzen/CLAUDE.md`
still apply (PHP 8.5, Pint, Pest, CI standards) — this file is the
package-specific layer, not a replacement.

## What this is

- Composer package `rayzenai/project-management`, type `library`, version `0.1.0`, MIT.
- PSR-4: `RayzenAI\ProjectManagement\` → `src/`; factories → `database/factories/`.
- Requires `php ^8.5`, `laravel/framework ^12 || ^13`. Dev: `orchestra/testbench`, `pestphp/pest ^4`.
- **Not an app.** It has no `phpunit.xml` and no `tests/` of its own. It is consumed
  by the host at `/Users/kiran/rayzen/kiranwork` via a **path repository with
  `symlink: true`** (`"rayzenai/project-management": "*"`). Edits here are live in the host immediately.
- Auto-discovered: `extra.laravel.providers` → `ProjectManagementServiceProvider`.

### What the service provider wires (`src/ProjectManagementServiceProvider.php`)

- `mergeConfigFrom` `config/project-management.php` (key `project-management`).
- `loadMigrationsFrom` `database/migrations/` and `loadRoutesFrom` **both**
  `routes/workspace.php` (web/Inertia) and `routes/api.php` (JSON).
- Defines the `manage-workspace` Gate from `config('project-management.super_admins')`
  **only if the host hasn't already defined it** (`Gate::has` guard).
- `Relation::enforceMorphMap([...])` for `task`, `project-note`, `project-contact`,
  `subtask`, `project-assignment`, `team`, `member` (merges with the host's map).
- Registers 5 observers: `Task`, `ProjectNote`, `ProjectContact`, `Subtask`,
  `ProjectAssignment` (these write the `ProjectActivity` audit log).
- Registers the `digest:send-weekly` console command (`SendProjectWeeklyDigest`) and
  the `workspace:prune-trashed` command (`PruneTrashedWorkspaceModels`, scheduled daily).
- Publishes the config under tag `project-management-config`.

## Commands

This package runs through the host app's test/lint tooling (it has none of its own).

```bash
# From the package dir — format only this package's PHP:
vendor/bin/pint --dirty --format agent      # (uses host vendor via symlink; or run from host root)

# From the host root (/Users/kiran/rayzen/kiranwork):
composer test                               # config:clear + php artisan test (Pest)
php artisan test --compact tests/Feature/Workspace
php artisan test --compact --filter=TeamPermissions
php artisan migrate                         # package migrations load automatically
php artisan digest:send-weekly --pretend    # dry-run the weekly digest
php artisan workspace:prune-trashed --pretend  # dry-run the soft-delete prune (force-deletes rows past trash_ttl_days)
```

## Configuration (`config/project-management.php`)

- `user_model` — host authenticatable, default `App\Models\User`. Every user
  relationship (`assignments`, `notes`, `contacts`, `subtasks`, `activities`,
  `Member::user`) resolves through this.
- `middleware` — applied to the **web** route group; default `['web', 'auth']`.
- `statuses` — the ordered 7-status task workflow, the single source of truth for
  board columns, chips, and dashboards: `not_started`, `unclear`, `in_progress`,
  `late`, `done` (`is_complete`), `done_late` (`is_complete`), `failed`. Each has
  `label`, `color`, `is_complete`. `Task::completeStatuses()` derives "finished"
  from the `is_complete` flags — never hardcode status strings.
- `complete_status` — status applied by the one-click complete checkbox (`done`).
- `super_admins` — emails holding `manage-workspace`; comma-separated via
  `PM_SUPER_ADMINS`. `super_admin_default_password` (`PM_SUPER_ADMIN_PASSWORD`,
  default `password`) is used by the host's `WorkspaceSuperadminSeeder`.

## Core architectural pattern (follow it exactly)

Request flow, mirrored across both surfaces:

```
Route → Controller (thin) → FormRequest::authorize() (authorization)
      → action Service (returns ServiceResult, never throws on expected failure)
      → JsonResource (output shape)
```

- **Controllers are thin** (`src/Http/Controllers/{Workspace,Api}/*`). They
  type-hint the FormRequest + the action service, call `$service->execute(...)`,
  and hand the `ServiceResult` to a trait. They contain no business logic. Cheap
  ownership checks may use `abort_unless(...)` inline (e.g. subtasks are personal:
  `abort_unless($subtask->user_id === $request->user()->id, 403)`).
- **Authorization lives in FormRequest `authorize()`** (`src/Http/Requests/*`),
  routed through `WorkspaceAccess` (see below). Validation rules + array-syntax
  rules in `rules()`. The same FormRequest is reused by both the web and API
  controllers — authorization is defined once.
- **Action services** (`src/Services/Workspace/*`) take primitives/models +
  validated attributes and return a **`ServiceResult`** (`src/Support/ServiceResult.php`):
  a `final readonly` DTO with `success(data, message, meta)` / `failure(message, code, data)`
  / `fromException(e)`. **Services NEVER throw on expected-failure paths** — they
  return `ServiceResult::failure('...', 422)`. They only `try/catch` truly
  exceptional conditions, `report($e)`, and return `ServiceResult::fromException($e)`.
- **`ServiceResult` → response** via traits:
  - Web: `Http/Controllers/Workspace/Concerns/RedirectsWithServiceResult` →
    Inertia redirect (`back()->with('workspace_flash', [...])`; errors via
    `withErrors`). The Svelte app reads the flash.
  - API: `Http/Controllers/Api/Concerns/RespondsWithServiceResult` → JSON.
    Success: `{message, data}` at `result->code` (or explicit `201`). Failure:
    `{message, errors}` at `result->code`. The ServiceResult `code` is the HTTP status.
- **Output** through Eloquent API Resources (`src/Http/Resources/*`), e.g.
  `SubtaskResource` (`$wrap = null`, ISO-8601 dates, `whenLoaded` for relations).
- Heavy read queries are factored into `src/Queries/*` (`ProjectIndexQuery`,
  `ProjectShowQuery`, `TaskShowQuery`).

When adding a feature: write the Service + FormRequest + Resource once, then add a
thin method to **both** the `Workspace\` and `Api\` controller. `Api\SubtaskController`
is the canonical reference — it documents itself as the "JSON sibling".

## Authorization model — SECURITY CRITICAL

`src/Support/WorkspaceAccess.php` is the **single source of truth** for every role
decision. Three tiers:

1. **Super-admin** — the config-driven `manage-workspace` Gate. Can do anything:
   create/delete/rename teams, manage every member, archive/restore any project,
   reassign members between teams.
2. **Team leader** — a member whose `member_team.role = 'leader'` (`Member::ledTeams`,
   `Team::leaders`). Scoped power over the teams they lead.
3. **Regular member** — assignable to tasks; manages only their own personal data
   (their subtasks, their workspace notes).

Public methods (all static, take `?Authenticatable`):

- `isSuperAdmin($user)` — `Gate::forUser($user)->allows('manage-workspace')`.
- `leadsTeam($user, $team)` — team id is in the user's led-team ids.
- `canManageRosterOf($user, $team)` — super-admin **or** leads that team. Gates
  add/remove members on a team.
- `canArchiveProject($user, $project)` — super-admin, or leads a team attached to the project.
- `canCreateMemberForTeams($user, $teamIds)` — super-admin anywhere; a leader only
  for teams they **all** lead. **A leader cannot create an unattached member**
  (empty `$teamIds` → false for non-super-admins); only super-admins create teamless members.
- `canManageMember($user, $member)` — may edit the member's attributes (name,
  email, password, active flag) — NOT team affiliations. True if the member shares
  any team the user leads.
- `ledTeamIds($user)` — the user's led team ids. Memoized per user instance via a
  request-scoped `WeakMap` to avoid N+1 (e.g. `canArchiveProject` per project in a
  list). Does **not** create a member as a side effect (unlike `Member::forUser`),
  so it is safe inside authorization.

### Privilege-escalation protections — DO NOT REGRESS

These guard real attack vectors found and fixed in recent commits. Future changes
must preserve them:

- **`canManageMember` blocks managing a super-admin's linked login** — a
  non-super-admin can never manage a member whose `user` is itself a super-admin,
  even when they legitimately share a team. Blocks the **attach-then-password-reset
  takeover** (commits 04ab4d9, 35ec30d).
- **Team rename + member team-reassignment are super-admin only** (commit 5c3b69e).
  Leaders manage rosters of their teams but cannot rename teams or move members
  between teams.
- **Team role changes require BOTH `canManageRosterOf($user, $team)` AND
  `canManageMember($user, $member)`** — see `UpdateTeamMemberRoleRequest::authorize()`
  (commit 5d673f2). This is the canonical example of composing two checks; replicate
  the pattern for any roster-role mutation.

Every controller, FormRequest, and resource that makes a role decision MUST go
through `WorkspaceAccess`. Never re-implement role logic inline.

## Domain model (`src/Models/`)

- **Project** — slug-routed (`getRouteKeyName() = 'slug'`). `archived_at` soft-archive
  (`archive()`/`restore()`, scopes `public`, `active`, `archived`, `isArchived`
  attribute). `hasMany Task`; `belongsToMany Team` (pivot `project_team`).
- **Task** — slug-routed (`{project_id}-{slug}`, generated in `booted()` saving hook).
  `belongsTo Project`; `hasMany` Subtask, ProjectAssignment, ProjectNote,
  ProjectContact, ProjectActivity. Plan fields (`item_number`, `category`,
  `deadline_type`, `responsible_ministry`, `title_np`, `description_np`) are stored
  in the `metadata` JSON column via `Attribute` mutators (kept `$fillable` for
  back-compat). Status/label/color/freshness are computed `$appends`. Scopes:
  `mine($user)`, `status`, `complete`/`incomplete` (driven by config `is_complete`),
  `forActiveProjects`, `category`, `orderByItemNumber`.
- **Subtask** — personal to a user (`user_id`); the "todos" on a task. Ownership
  enforced inline in the controller, not via WorkspaceAccess.
- **Member** — a person in the workspace; what tasks get assigned to. Optionally
  links to a host user (`user_id`, one member per user). `Member::forUser($user)`
  `firstOrCreate`s the member (so new logins just work — do **not** call this in
  authorization paths). `belongsToMany Team` with pivot `role`; `ledTeams()` =
  pivot role `leader`. `scopeAssignableFor(Project)` is the single source of truth
  for who can be assigned (project's teams' members, or all active members if the
  project has no teams).
- **Team** — auto-slugged in `booted()`. `belongsToMany Member` (pivot
  `member_team`, with `role`), `leaders()`, `belongsToMany Project` (pivot
  `project_team`).
- **ProjectActivity** — polymorphic audit log (`subject` morphTo), written **only**
  by observers. `ACTION_*` constants (`created`, `updated`, `deleted`,
  `status_changed`, `progress_changed`, `completed`, `reopened`). Scopes `public`, `recent`.
- **WorkspaceNote** — personal sticky notes (`user_id`, `position_x/y`, `color` ∈
  `COLORS`). Surfaced everywhere via `ShareWorkspaceData`.
- **ProjectAssignment / ProjectNote / ProjectContact** — task children.
- **ProjectDigestSubscriber** — weekly digest recipients (`dueForWeekly`,
  optional `categories` filter).

## Two delivery surfaces

- **Web / Inertia** (`routes/workspace.php`, prefix `workspace`, name `workspace.`):
  middleware `config('project-management.middleware')` + `ShareWorkspaceData`.
  Controllers return Inertia redirects. Svelte pages/components live in
  `resources/js/Pages/` and `resources/js/components/`.
- **API / JSON** (`routes/api.php`, prefix `api/v1`, name `api.`): Sanctum bearer auth.
  - `POST api/v1/login` (email + password + `device_name`) → `{token, user}`.
  - `POST api/v1/logout`, `GET api/v1/user` (workspace context) under `auth:sanctum`.
  - `api/v1/workspace/*` mirrors the web routes and **reuses the same services,
    FormRequests, and JsonResources** — only the response envelope differs
    (`{message, data}` / `{message, errors}`, status from `ServiceResult->code`).
  - `Api\AuthController` is the token-auth equivalent of `ShareWorkspaceData`
    (member profile, `is_super_admin`, `led_team_ids`).

### `ShareWorkspaceData` (`src/Http/Middleware/ShareWorkspaceData.php`)

Web-only middleware. Shares Inertia props on every workspace page so the Svelte app
needs no extra fetch: `statuses` (config workflow), `completeStatus`,
`quickAddContext` (active projects + active members + `currentMemberId`),
`workspaceNotes` (the user's notes), `isSuperAdmin`, `ledTeamIds`. When adding
cross-cutting web context, add it here; for the API, add it to `Api\AuthController::userPayload`.

## Conventions

- PHP 8.5; constructor property promotion; explicit return types on all
  methods/functions; always use curly braces; PHPDoc array shapes over inline comments.
- Use `php artisan make:` for new files. New models get a factory in
  `database/factories/` (`newFactory()` wired on the model).
- Slugs: projects and tasks are slug-routed (`{project:slug}` / `{task:slug}` with
  `->scopeBindings()`); teams auto-slug. Never expose numeric ids in routes.
- Any model mutation that needs an audit trail goes through an **observer** writing
  `ProjectActivity` (via `ProjectActivityRecorder`) — don't write activity rows in
  services or controllers.
- New entities that can be a polymorphic `subject` must be added to the morph map in
  the service provider.
- Run `vendor/bin/pint --dirty --format agent` before finalizing.

## Tests

The package ships no test suite. All tests live in the **host app** under
`tests/Feature/Workspace/` (e.g. `TeamPermissionsTest`, `WorkspaceRolesTest`,
`MemberPermissionsTest`, `ProjectArchiveActionTest`, `StatusEngineTest`,
`QuickAddParserTest`). API feature tests belong under `tests/Feature/Workspace/Api/`.
When you change authorization or a service here, add/extend the matching Pest
feature test in the host — especially happy + failure + privilege-escalation paths
for anything touching `WorkspaceAccess`.
