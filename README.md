# rayzenai/project-management

A project & task tracking **workspace** (projects → tasks → subtasks, assignments,
notes, contacts, teams, members, activity log, notifications, weekly digest) for a
Laravel + Inertia (Svelte) host application, with a JSON API for mobile clients.

- PHP `^8.5`, Laravel `^12 || ^13`.
- Web/Inertia surface at `/workspace/*` (session auth) and a JSON API at
  `/api/v1/*` (Sanctum token auth).

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

The workspace renders through its **own** Inertia app (separate bundle from your
host's pages). Add a root Blade view and a Vite entry, and switch to them for
`/workspace/*` requests.

`resources/views/workspace.blade.php` — load the workspace CSS + JS entry and
`@inertia`/`@inertiaHead` (see this host's copy for the full template):

```blade
@vite(['packages/project-management/resources/js/styles/workspace.css', 'resources/js/workspace/app.ts'])
```

`resources/js/workspace/app.ts` — an Inertia + Svelte entry whose `resolve()`
globs the package's pages:

```ts
import.meta.glob('../../../packages/project-management/resources/js/Pages/**/*.svelte', { eager: true });
```

`vite.config.*` — add both inputs:

```ts
input: [
    /* …your host inputs… */
    'packages/project-management/resources/js/styles/workspace.css',
    'resources/js/workspace/app.ts',
],
```

Your `HandleInertiaRequests` middleware returns the workspace root view for
`/workspace` paths:

```php
public function rootView(Request $request): string
{
    return $request->is('workspace', 'workspace/*') ? 'workspace' : $this->rootView;
}
```

### 3. API auth (mobile clients)

The API surface uses Sanctum bearer tokens: `POST /api/v1/login`
(email + password + `device_name`) returns `{ token, user }`; `POST /api/v1/logout`
revokes the current token. No extra host wiring beyond having Sanctum installed.

## Auth surfaces at a glance

| Surface | Login | Logout | Auth |
| --- | --- | --- | --- |
| Web (Inertia) | `GET/POST /workspace/login` | `POST /workspace/logout` | session (`web` guard) |
| API (mobile) | `POST /api/v1/login` | `POST /api/v1/logout` | Sanctum token |

The web login supports **remember-me**, **show/hide password**, and is **rate
limited** (5 attempts per email + IP per minute).

## Development

This package has no test suite of its own — it is exercised by the host app's Pest
suite under `tests/Feature/Workspace/`. Run `vendor/bin/pint --dirty` before
committing. See `CLAUDE.md` in this directory for the full architecture guide.
