# rayzenai/project-management

A project & task tracking **workspace** (projects → tasks → subtasks, assignments,
notes, contacts, teams, members, activity log, notifications, weekly digest) for a
Laravel + Inertia (Svelte) host application, with a JSON API for mobile clients.

- PHP `^8.5`, Laravel `^12 || ^13`.
- Web/Inertia surface at `/workspace/*` (session auth) and a JSON API at
  `/api/v1/*` (Sanctum token auth).

## Concepts

> Projects are private to the teams attached to them; attach a team to grant
> access; public projects are visible to everyone.

> Creating a project requires at least one team. A user with no teams is shown a
> team-creation step first (team creation is super-admin-only), then the project
> form. Team leaders always have a team, so they go straight to the project form.

## Installation

Require the package (this repo consumes it via a path repository with `symlink: true`):

```json
"repositories": [
    { "type": "path", "path": "packages/project-management", "options": { "symlink": true } }
],
"require": {
    "rayzenai/project-management": "*"
}
```

```bash
composer require rayzenai/project-management
php artisan migrate
```

The service provider is auto-discovered. It registers the migrations, the
`/workspace` (web) and `/api/v1` (API) routes, the `manage-workspace` gate, the
model observers, the morph map, and the scheduled commands. Optionally publish the
config:

```bash
php artisan vendor:publish --tag=project-management-config
```

Set your super-admins and (optionally) the user model in `.env` / the published
config:

```env
PM_SUPER_ADMINS="you@example.com"
```

## Host integration (manual steps)

A few things live in the **host app** because the package can't reach into your
bootstrap, Inertia, or Vite config. Wire these up once.

### 1. Guest redirect → the branded workspace login  ⚠️ required

The workspace ships its own branded login page at **`GET /workspace/login`**
(route name `workspace.login`). Unauthenticated visitors to any `/workspace/*`
route must be sent there. In `bootstrap/app.php`, make the guest redirect
path-aware so the workspace uses its login while the rest of your app (e.g. a
Filament admin panel) keeps its own:

```php
use Illuminate\Http\Request;

->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo(fn (Request $request): string =>
        $request->is('workspace', 'workspace/*')
            ? route('workspace.login')
            : '/admin/login'); // your app's own login for everything else
})
```

If your app has no other authenticated area, you can simply use
`$middleware->redirectGuestsTo(fn () => route('workspace.login'));`.

After logout (`POST /workspace/logout`) the user is returned to this same login
page via a full-page Inertia location visit.

### 2. Inertia root view + Vite entry for the workspace SPA

The workspace renders through its **own** Inertia app — a separate JS bundle and a
separate HTML shell from your host's own pages. Every Inertia page needs an HTML
document to mount into, so the workspace ships **two host files** you must create:

- `resources/views/workspace.blade.php` — the root Blade view (the `@inertia`
  shell that loads the workspace bundle).
- `resources/js/workspace/app.ts` — the Inertia + Svelte entry whose `resolve()`
  globs the package's `Pages/`.

**Publish them (recommended):**

```bash
php artisan vendor:publish --tag=project-management-host
```

This drops both files into place. Prefer to **copy manually**? Grab them from
`vendor/rayzenai/project-management/resources/stubs/workspace.blade.php` and
`…/resources/stubs/workspace-app.ts` and place them at the two paths above.

> **Adjust the package path inside both files.** The stubs reference
> `vendor/rayzenai/project-management/…` (a normal `composer require` install). If
> you consume the package via a **path repository / monorepo** (as this repo does),
> change those references to `packages/project-management/…`. The `@vite([...])`
> path in the Blade and the two `import`/`import.meta.glob` paths in `app.ts` must
> all point at wherever the package actually lives, and must match your Vite input.

Then wire the two things the package **can't** publish (they edit existing files):

`vite.config.*` — add both inputs (match the path you used above):

```ts
input: [
    /* …your host inputs… */
    'vendor/rayzenai/project-management/resources/js/styles/workspace.css',
    'resources/js/workspace/app.ts',
],
```

`HandleInertiaRequests` — return the workspace root view for `/workspace` paths:

```php
public function rootView(Request $request): string
{
    return $request->is('workspace', 'workspace/*') ? 'workspace' : $this->rootView;
}
```

Without `workspace.blade.php` every `/workspace/*` route (including
`/workspace/login`) throws *“View [workspace] not found”* — it is the load-bearing
HTML shell for the whole workspace SPA, not an optional file.

### 3. API auth (mobile clients)

The JSON API uses Sanctum bearer tokens. No extra host wiring beyond having
Sanctum installed and a `personal_access_tokens` table migrated.

## HTTP API reference

The package exposes the workspace through **two HTTP surfaces that share the same
services, FormRequest authorization, and JsonResources** — only the response
envelope differs:

- **JSON API** — prefix `api/v1`, route names `api.*`, **Sanctum bearer** auth.
  Built for mobile clients (the Flutter app in `../task-management`).
- **Web (Inertia)** — prefix `workspace`, route names `workspace.*`, **session**
  auth (`web` guard). Returns Inertia redirects carrying a `workspace_flash`
  instead of JSON.

### Conventions (JSON API)

- **Base:** `${APP_URL}/api/v1`. **Auth:** `Authorization: Bearer <token>` on
  everything except `POST /login`. Send `Accept: application/json`.
- **Success:** `{ "message": string, "data": <resource|array> }` — `200`, or
  `201` on create.
- **Error:** `{ "message": string, "errors": { field: message } }` — `401`
  unauthenticated, `403` forbidden, `404` not found, `422` validation.
- **Authorization** is enforced server-side via `WorkspaceAccess` (three tiers:
  super-admin → team-leader → member). Clients mirror role gating in the UI only.
- **Project visibility:** a project is visible to a user iff `is_public` OR the
  user's member is in a team attached to the project OR the user is a super-admin.
  Implemented as `Project::scopeVisibleTo($user)` and enforced on the project index
  (web + API), project/task `show` (403 otherwise), dashboard, My Workspace, search,
  and the quick-add picker. Relevant `WorkspaceAccess` gates:
    - `canViewProject($user, $project)` — the visibility rule above (drives the `show` 403).
    - `canCreateProject($user)` — super-admin, or a user who leads ≥1 team.
    - `canManageProjectAccess($user, $project)` — super-admin, or a user who leads a
      team attached to the project. Gates project update/access changes;
      `canArchiveProject` delegates to it.
- **Soft delete + undo:** every `DELETE` soft-deletes; each resource has a
  matching `…/restore` (authorization for restore equals authorization for the
  delete). Trashed rows are pruned after `trash_ttl_days` (default 30).
- **Timestamps** are ISO-8601 (UTC). Projects and tasks are **slug-routed**.

### Account & session

| Method | Path | Body | Returns |
| --- | --- | --- | --- |
| POST | `/login` | `email`, `password`, `device_name` | `{ token, user }` |
| POST | `/logout` | — | `{ message }` (revokes the current token) |
| GET | `/user` | — | the caller's workspace context: `{ id, name, email, member, is_super_admin, led_team_ids }` |

The **web** surface logs in via a branded page instead (see *Auth surfaces*
below).

### Notifications (in-app inbox)

Top-level under `/api/v1` (not the `workspace` prefix). In-app only — email/push
are deferred.

| Method | Path | Returns |
| --- | --- | --- |
| GET | `/notifications?page=` | paginated `[Notification]`, newest first (`data` + `meta` + `links`) |
| GET | `/notifications/unread-count` | `{ data: { count } }` |
| POST | `/notifications/{id}/read` | `{ message }` (sets `read_at`) |
| POST | `/notifications/read-all` | `{ message }` |

### Feeds & overview  (`/workspace/*`)

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/workspace/dashboard` | per-project rollups, status breakdown, recent activity |
| GET | `/workspace/my` | the caller's focused tasks, open todos, assigned work |
| GET | `/workspace/plan-tracker` | the 100-point plan tracker |
| GET | `/workspace/search?q=` | task search across active projects |
| POST | `/workspace/quick-add` | `{ text }` natural-language task capture |

### Projects

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/workspace/projects` | list — **visibility-scoped** (only projects visible to the caller; see *Concepts* + *Conventions*). `?archived=1` for archived |
| POST | `/workspace/projects` | create. Body accepts `team_ids: int[]` (required & non-empty **unless** `is_public`; non-super-admins may only attach teams they lead) and `is_public: bool` (**super-admin only** — silently ignored from others). The web UI presents a team-creation step first when the caller has no teams |
| GET | `/workspace/projects/{slug}` | show (board + tasks) — **403** if the project isn't visible to the caller |
| PATCH | `/workspace/projects/{slug}` | update. Same `team_ids` / `is_public` rules as create |
| PATCH | `/workspace/projects/{slug}/archive` | soft-archive (leader of an attached team / super-admin) |
| PATCH | `/workspace/projects/{slug}/restore` | un-archive |

### Tasks

| Method | Path | Notes |
| --- | --- | --- |
| POST | `/workspace/projects/{slug}/tasks` | create |
| POST | `/workspace/projects/{slug}/tasks/reorder` | persist board ordering |
| GET | `/workspace/projects/{slug}/tasks/{taskSlug}` | task hub (incl. `comments`) |
| PATCH | `/workspace/projects/{slug}/tasks/{taskSlug}` | update (status, fields) |
| DELETE | `/workspace/projects/{slug}/tasks/{taskSlug}` | soft-delete |
| POST | `/workspace/projects/{slug}/tasks/{taskSlug}/restore` | undo delete |
| GET | `/workspace/tasks/{task}/preview` | lightweight peek (incl. `comments_count`) |

### Subtasks  *(personal to the caller)*

| Method | Path |
| --- | --- |
| POST | `/workspace/tasks/{task}/subtasks` |
| PATCH | `/workspace/subtasks/{subtask}` |
| DELETE | `/workspace/subtasks/{subtask}` |
| POST | `/workspace/subtasks/{subtask}/restore` |

### Assignments

| Method | Path |
| --- | --- |
| POST | `/workspace/tasks/{task}/assignments` |
| PATCH | `/workspace/assignments/{assignment}` |
| DELETE | `/workspace/assignments/{assignment}` |
| POST | `/workspace/assignments/{assignment}/restore` |

### Comments + @mentions

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/workspace/tasks/{task}/comments?page=` | paginated `[TaskComment]` |
| POST | `/workspace/tasks/{task}/comments` | `{ body }` (201) |
| PATCH | `/workspace/comments/{comment}` | author-only |
| DELETE | `/workspace/comments/{comment}` | author-only soft-delete |
| POST | `/workspace/comments/{comment}/restore` | author-only |

Mentions are stored canonically as `@[Display Name](member:ID)`; the resolved
`mentions` array on each comment is authoritative.

### Task notes & contacts

| Method | Path |
| --- | --- |
| POST | `/workspace/tasks/{task}/notes` |
| DELETE | `/workspace/notes/{note}` |
| POST | `/workspace/notes/{note}/restore` |
| POST | `/workspace/tasks/{task}/contacts` |

### Personal workspace notes  *(draggable stickies, owner-only)*

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/workspace/my-notes` | the caller's notes board: `{ data: { workspace_notes, task_notes } }` — own stickies (newest first) plus task notes they authored or that live on a task assigned to them (latest 50). Mirrors what the web shares via Inertia. |
| POST | `/workspace/my-notes` | create |
| PATCH | `/workspace/my-notes/{note}` | edit body/color |
| PATCH | `/workspace/my-notes/{note}/placement` | move (`position_x/y`) |
| DELETE | `/workspace/my-notes/{note}` | soft-delete |
| POST | `/workspace/my-notes/{note}/restore` | undo |

### Teams  *(roster: leader/super-admin; rename + reassignment: super-admin only)*

| Method | Path |
| --- | --- |
| GET | `/workspace/team` |
| POST | `/workspace/teams` |
| PATCH | `/workspace/teams/{team}` |
| DELETE | `/workspace/teams/{team}` |
| POST | `/workspace/teams/{team}/restore` |
| POST | `/workspace/teams/{team}/members` |
| DELETE | `/workspace/teams/{team}/members/{member}` |
| PATCH | `/workspace/teams/{team}/members/{member}` (role) |

### Members

| Method | Path |
| --- | --- |
| POST | `/workspace/members` |
| PATCH | `/workspace/members/{member}` |
| DELETE | `/workspace/members/{member}` |
| POST | `/workspace/members/{member}/restore` |

### Appearance, preferences & themes  *(host-provided)*

These three live in the **host app's** `routes/api.php`
(`ThemeController` / `PreferenceController`), not the package, but are part of the
workspace contract — the appearance UI and Flutter client consume them. The web
app also exposes a session twin at `PATCH /workspace/preferences`.

| Method | Path | Body | Returns |
| --- | --- | --- | --- |
| GET | `/themes` | — | `{ data: { themes: [Theme], font_allow_list } }` |
| GET | `/user/preferences` | — | `{ data: Preferences }` |
| PATCH | `/user/preferences` | `theme?`, `font_override?`, `email_notifications?` | `{ data: Preferences }` |

### Resource shapes & the canonical contract

This README lists the **endpoint surface**; for full request/response payloads
(`Notification`, `TaskComment`, `Preferences`, `Theme`, the mention token format,
and the undo/restore client guidance) see the single-source-of-truth contract:

- `docs/api/workspace-api.md` — the versioned JSON API contract.
- `docs/superpowers/specs/2026-06-16-appearance-themes-fonts-design.md` — the full
  theme token table (the contract for both web `--ws-*` and Flutter `ThemeData`).

## Auth surfaces at a glance

| Surface | Login | Logout | Auth |
| --- | --- | --- | --- |
| Web (Inertia) | `GET/POST /workspace/login` | `POST /workspace/logout` | session (`web` guard) |
| API (mobile) | `POST /api/v1/login` | `POST /api/v1/logout` | Sanctum token |

The web login page (`/workspace/login`) supports **remember-me**, **show/hide
password**, and is **rate limited** (5 attempts per email + IP per minute). The
web surface also serves a notifications inbox page (`GET /workspace/notifications`,
plus `read`/`read-all` POSTs) and `PATCH /workspace/preferences`; every other web
route mirrors the JSON API table above 1:1.

## Development

This package has no test suite of its own — it is exercised by the host app's Pest
suite under `tests/Feature/Workspace/`. Run `vendor/bin/pint --dirty` before
committing. See `CLAUDE.md` in this directory for the full architecture guide.
