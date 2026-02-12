<!-- Copilot instructions for phpVB / gov2framework -->
# Copilot / AI agent quick guide

This file helps AI coding agents become productive quickly in this repository.

1. Big picture
- Entry point: `public/index.php` loads `core/init/index.php`.
- Boot/route: `core/init/index.php` -> `core/init/route.php` (uses FastRoute).
- Framework code (library): PSR-4 `Gov2lib\` lives under `core/lib` (see `composer.json`).
- Application code: `App\` maps to `apps/` (models, views, vue components live per-app).
- Routes: merged from `core/config/route.xml` + `apps/{app}/xml/route.xml` and dispatched by FastRoute.

2. How requests are dispatched (important pattern)
- URL -> `public/index.php` -> `core/init/index.php` -> `route.php`.
- `route.php` resolves handler strings to either `Gov2lib\...` handlers or `App\{app}\model\{ClassName}`.
- Handlers: model classes are instantiated and the `cmd` parameter selects a method. If `$_POST` or JSON body present, `cmd` maps to method names.

3. Session, auth, and state
- Sessions use a JWT cookie named `Gov2Session` managed by `core/lib/gov2session.php`.
- Exceptions and rendering helpers are in `core/lib/document.php` (templates, components, Vue data).

4. Development / run steps (what to run)
- Install PHP deps: `composer install` (project targets PHP 8.1 as per `composer.json`).
- DB: run `apps/home/sql/sql.sql` and set credentials in `apps/home/xml/dsnSource.local.xml` (see README setup sections).
- Webserver: point DocumentRoot to `public/` (local dev examples in `README.md`).
- MeekroDB change: README instructs adding three public static properties into `sergeytsalkov/meekrodb/db.class.php` (line ~92) — keep this in mind when running locally.

5. Project-specific conventions and patterns
- App model classes extend Gov2lib base classes (e.g. `
  class index extends \Gov2lib\document` in `apps/*/model/*.php`).
- Template loading: Twig paths and app templates are added by the `document` object; app templates live in the app `view/` directories and Vue components under `apps/{app}/vue/`.
- XML-driven configuration: many behaviours are driven by `apps/{app}/xml/*.xml` (route, config, pageroles, dataroles, superuser, etc.). Prefer reading those XMLs when modifying auth or routes.
- Route handlers string format: `Namespace\\Class` in XML — `route.php` converts that to fully qualified class names.

6. Key files to consult (quick links)
- `public/index.php`
- `core/init/index.php`
- `core/init/route.php`
- `core/lib/document.php`
- `core/lib/gov2session.php`
- `core/config/route.xml`
- `composer.json` (PSR-4 autoload, PHP platform)
- Example app: `apps/examples/` (shows `model/`, `view/`, `vue/`, and `xml/route.xml`)

7. Editing guidance for agents
- When adding a new HTTP route, update `apps/{app}/xml/route.xml` and add a corresponding model class in `apps/{app}/model/` (class name must match handler mapping used by `route.php`).
- For server-side JSON endpoints: ensure `$_POST` or `php://input` parsing is correct; `route.php` will populate `$vars` for GET routes.
- For auth changes verify both `core/lib/gov2session.php` and `apps/{app}/xml/pageroles.xml` (or `core/config/route.xml`) to avoid inconsistent access behaviour.

8. Testing and debugging tips
- To inspect routing at runtime, enable `STAGE=dev` in config and trigger an invalid route to see thrown exceptions (handled by `document->exceptionHandler`).
- To reproduce API calls locally, use `curl` with appropriate `Accept: application/json` headers so `route.php` treats the request as `ajax`.

If anything here is unclear or you want more detail (examples of route XML, sample model method, or common templates), tell me which section to expand. I'll iterate the file.
