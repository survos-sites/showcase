# Survos/Museado Conventions

Platform-wide conventions for any repo in the Survos/Museado ecosystem. When working in a specific repo, also read that repo's `AGENTS.md` and `PLAN.md`.

## Symfony commands — the canonical pattern

Commands live as **methods on a service class**, not as standalone command classes. We run **Symfony 8.1+ everywhere**, so `#[AsCommand]` always goes on a **method**, never on a class — one service class exposes a whole family of related commands that share a single constructor, its injected services, and private helpers.

### Rules

- `#[AsCommand('name', 'description')]` **always on a method, never on a class.** Positional description (second argument), not the `description:` named parameter. Same convention as `#[Argument]` and `#[Option]`.
- **Never `extends Command`.** Import `Symfony\Component\Console\Command\Command` only for the return constants.
- Return `Command::SUCCESS` / `Command::FAILURE` / `Command::INVALID`.
- `#[Argument('desc')]` and `#[Option('desc')]` — positional description string. Never `description:` named param.
- **Inject services through the constructor, never into the method.** The method signature is *only* `SymfonyStyle $io` (or `OutputInterface $output` when leaner) plus the command's `#[Argument]` / `#[Option]` / `#[MapInput]` parameters. Autowired services belong in the constructor — and that shared dependency set is precisely the reason sibling commands group into one class.
- **Group multiple commands in one class when they share services.** Each is a method; they reuse the constructor-injected services and private helpers. Splitting only when a method's helpers stop being shared (see below).
- One class holds a cohesive *family* of commands (`app:load`, `app:update`, `app:status` together in `AppService`; `site:add`, `site:list`, `site:scan` together in `SiteService`). The class name reflects its primary identity as a service, not as a command holder: `AppService`, not `AppCommands`.
- When a method's private helpers stop being shared with siblings in the class, that's the signal to split into a new class. Don't make god-classes. Don't make one-class-per-command.
- Class names: `*Service`. The CLI is just another transport into the service layer.

### Typed inputs — use value resolvers and DTOs

Validation, normalization, and parsing belong in value objects and their resolvers, not in command bodies.

- **Single typed atoms** (URL, email, ULID, path) — value objects with a custom `ValueResolverInterface`. The value object's `fromString()` factory validates; the resolver maps raw CLI string to the typed object. Used as `#[Argument] SiteUrl $url`.
- **Groups of related inputs** (multiple args/options that travel together) — `#[MapInput]` DTO classes. Public properties carry `#[Argument]` / `#[Option]` attributes. Validation belongs in property hooks (PHP 8.4) or via Symfony Validator — *not* in the constructor, since `MapInput` DTOs are hydrated without calling the constructor.
- **Composition** — DTOs can contain other DTOs. `ApplyInput { public ScanInput $scan; /* + options */ }`. Symfony merges them automatically.
- **Why this matters** — a description like `'url of the site to monitor'` lives once on the value object or DTO, not repeated across five command methods. Same for validation logic.

Use the Symfony Validator component for validation constraints (`#[Assert\Url]`, `#[Assert\Email]`, etc.) — it's idiomatic and integrates with the rest of Symfony.

### Reference example

```php
namespace App\Service;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SiteService
{
    public function __construct(
        private SiteRepository $sites,
        private EntityManagerInterface $em,
        private HttpClientInterface $http,
    ) {}

    #[AsCommand('site:add', 'add a site to monitor')]
    public function add(SymfonyStyle $io, #[Argument] SiteUrl $url): int { /* ... */ }

    #[AsCommand('site:scan', 'scan monitored sites for availability')]
    public function scan(SymfonyStyle $io, #[MapInput] ScanInput $input): int { /* ... */ }

    private function findOrFail(SiteUrl $url): Site { /* shared helper */ }
}
```

## PHP and Symfony

- PHP 8.4+, Symfony 8.1+.
- Multiple classes per file when tightly related.
- Constructor property promotion. `readonly` where it fits. Enums over string constants. Typed everything.
- No `dump()`, `dd()`, or commented-out code in committed work. Verbose output via `$io->writeln()` gated on `$io->isVerbose()` / `$io->isVeryVerbose()`.

### camelCase for data keys everywhere

All data keys — normalized JSONL fields, folio `dtoData` JSON keys, Meilisearch document fields, API response properties, Twig variable names — use **camelCase**. This matches PHP property names, JavaScript convention, and Symfony serialization defaults.

Snake_case is only for:
- SQL column names (Doctrine's `naming_strategy.underscore` handles this automatically)
- Raw source data we don't control

The `ItemField` and `MuseumVocab` constants hold camelCase values (`ItemField::IIIF_BASE = 'iiifBase'`). Normalizers output camelCase keys. The constant NAMES remain SCREAMING_SNAKE (PHP convention for constants), but their VALUES are camelCase strings.

### Prefer Symfony components over hand-rolled PHP

Treat Symfony components as if they were part of PHP itself — battle-tested, better error handling, consistent API. Never re-implement what a component already does.

| Instead of | Use |
|------------|-----|
| `glob()` | `Finder` (`symfony/finder`) |
| `mkdir()`, `file_put_contents()`, `rename()` | `Filesystem` (`symfony/filesystem`) |
| `preg_replace` / custom slugify | `AsciiSlugger` (`symfony/string`) |
| `str_*` chains on untrusted input | `UnicodeString` / `ByteString` (`symfony/string`) |
| `json_decode` without error handling | `symfony/serializer` or at minimum assert the result |
| Hand-rolled URL parsing | `symfony/http-foundation` `Request`/`UriSigner` |

The rule: if a Symfony component solves it, use the component. Only reach for raw PHP functions when there is no component equivalent or the overhead is genuinely unjustified (tight loop, no I/O).

### Mandatory libraries — never hand-roll these

These are not preferences. If the package is missing, `composer require` it — do **not** write your own.

- **All JSONL reading and writing MUST go through `survos/jsonl-bundle`** (`Survos\JsonlBundle\IO\JsonlReader` / `JsonlWriter`). Never read JSONL with `file()`/`fgets()`/`json_decode()` line loops, and never write it with `fopen`/`fwrite`/`fputs`. This includes one-off helpers, config files, and "just listing a few rows" — `JsonlReader::open($path)` is the only sanctioned reader. (Plain byte concatenation of already-valid JSONL files is not "reading records" and may use a stream copy.)
- **All byte-size displays MUST use `zenstruck/bytes`** (`Zenstruck\Bytes::parse($bytes)`). Never hand-roll a `humanBytes()` / KB-MB-GB formatter. `Bytes::parse((int) filesize($path))` is the idiom.

### Deprecations — check before using, fix when found

Before using any class, trait, or interface from a Survos bundle or Symfony, check for:
- `@deprecated` docblock on the class/method
- `trigger_deprecation()` call in the body
- `#[\Deprecated]` attribute (PHP 8.4+)

If deprecated: use the replacement if one is documented. If the replacement is in a different bundle that isn't yet a dependency, flag it in `PLAN.md` rather than working around silently.

When touching existing code that uses a deprecated symbol, fix it in the same PR — don't leave known deprecations in code you've already opened.

Example: `Survos\CoreBundle\Entity\RouteParametersTrait` is `@deprecated` — replaced by `#[RouteIdentity]` + `RouteIdentityTrait` from field-bundle. Any class still using `RouteParametersTrait` should be migrated on contact.

## Entities

PHP 8.4 + Doctrine ORM 3.x style — no boilerplate.

- **Public properties, no getters/setters.** Doctrine 3 hydrates public properties directly. Only add a method when it has real logic.
- **`readonly` for identity fields** set in the constructor (e.g. the PK). Doctrine uses reflection to hydrate readonly properties.
- **Property hooks for computed/virtual fields** instead of methods:
  ```php
  public string $githubUrl {
      get => sprintf('https://github.com/%s', $this->composerName);
  }
  public ?string $liveUrl {
      get => $this->site?->productionUrl;
  }
  ```
- **`final class`** for entities unless inheritance is required.
- **`declare(strict_types=1)`** in every PHP file.
- Natural/business-key PKs preferred over ULID when the key is stable and globally unique (e.g. composer name slug `survos__jsonl-bundle`). ULID for entities without a natural key.

### field-bundle attributes — use these, don't reinvent

`survos/field-bundle` provides the canonical metadata layer for all entities. Import from `Survos\FieldBundle\Attribute\`.

- **`#[EntityMeta(icon, group, label, description)]`** — class-level. Admin UI, dashboard, menu auto-registration. Discovered at compile time.
- **`#[Field(searchable, sortable, filterable, facet, widget, order, ...)]`** — property-level. Controls DataTables columns, Meilisearch index settings, filter widgets. Intentionally orthogonal to `#[ORM\Column]` and `#[ApiProperty]`.
- **`#[RouteIdentity(field: 'code')]`** — class-level. Declares which property identifies this entity in URLs. Replaces the legacy `UNIQUE_PARAMETERS` const pattern.

Every entity that appears in routes **must** implement `RouteParametersInterface` and use `RouteIdentityTrait`. Both now live in `survos/field-bundle` (`Survos\FieldBundle\Entity\RouteParametersInterface`) — the interface has fully migrated off `survos/core-bundle`. A `Survos\CoreBundle\Entity\RouteParametersInterface` still exists for now, marked `@deprecated` in its own docblock ("keep `implements RouteParametersInterface` on existing entities until field-bundle fully replaces this interface") — don't reach for it in new code, use field-bundle's.

```php
use Survos\FieldBundle\Attribute\RouteIdentity;
use Survos\FieldBundle\Entity\RouteIdentityTrait;
use Survos\FieldBundle\Entity\RouteParametersInterface;

#[RouteIdentity(field: 'code')]
final class Component implements RouteParametersInterface
{
    use RouteIdentityTrait;

    #[ORM\Id]
    #[ORM\Column(length: 128)]
    public readonly string $code;
}
```

This unlocks `entity.rp` in Twig, so route generation never hard-codes field names:

```twig
{# correct — field-agnostic #}
{{ path('component_show', component.rp) }}

{# wrong — breaks when the PK changes #}
{{ path('component_show', {code: component.code}) }}
```

**Extra route params beyond the entity's own identity** (e.g. a route scoped by both a folio and a core: `/{folioCode}/search/{coreCode}`) go through `getRp()`'s optional merge argument, never a hand-assembled array:

```twig
{# correct — entity supplies folioCode, the extra param merges in #}
{{ path('survos_folio_core_search', folio.rp({coreCode: core.code})) }}

{# wrong — reconstructing folioCode by hand defeats the whole point of RouteIdentity #}
{{ path('survos_folio_core_search', {folioCode: folio.code, coreCode: core.code}) }}
```
```php
// same rule in PHP
$folio->getRp(['coreCode' => $core->code]);
```
The merge is last-wins (`array_merge($params, $extras)` in `RouteIdentityResolver::paramsFor()`), so `$extras` can also override the entity's own key when needed — e.g. building a locale-suffixed variant of the same folio code: `$folio->getRp(['folioCode' => $folio->code . '.' . $locale])`.

If field-bundle lacks a needed capability, flag it as a field-bundle issue. Don't work around silently.

### Menu route parameters — the same rule, and where it actually broke down

This is the specific way the `entity.rp` rule above gets violated in practice, and it's caused real production outages (2026-07-19: folio-bundle's routing migration from `{provider}/{dataset}` to a single `{folioCode}` broke every app-level menu class that had hand-built `['provider' => ..., 'dataset' => ...]` arrays instead of using an entity's `.rp` — a one-line, entity-only change everywhere else, a multi-file emergency fix in every app that had drifted).

**The mechanism**, end to end:

1. A base/layout template resolves the relevant entities ONCE per request (usually already available as a controller-passed variable, or via a request attribute a listener set for routes that don't go through that controller) and registers them as menu options:
   ```twig
   {% set _folio = folio|default(app.request.attributes.get('_folio')) %}
   {% do tabler_menu_options({tenant: _tenant, folio: _folio}, _self) %}
   ```
   Every key used here **must** be declared under `survos_tabler.menu_options` in `config/packages/survos_tabler.yaml`, or `OptionsResolver` rejects it as unrecognized:
   ```yaml
   survos_tabler:
       menu_options:
           tenant: null
           folio: null
   ```
2. Menu-listener classes (`#[AsEventListener(event: MenuEvent::...)]`, using `Survos\TablerBundle\Traits\KnpMenuHelperTrait`) read the entity back via `$event->getOption('folio')` — **never** re-resolve it themselves from a code/string (that's a second source of truth that silently drifts out of sync with whatever the template/listener actually set).
3. `KnpMenuHelperTrait::add()`'s `$rp` parameter accepts `array|RouteParametersInterface|null` — pass the **entity itself** (or `$entity->getRp($extras)` when an extra param is needed), not a manually assembled array:
   ```php
   // correct
   $this->add($menu, 'survos_folio_map', $folio, icon: 'tabler:map-2');
   $this->add($menu, 'survos_folio_core_search', $folio->getRp(['coreCode' => $coreCode]), icon: 'tabler:search');

   // wrong — exactly what broke when the route's param shape changed
   $this->add($menu, 'survos_folio_map', ['provider' => $provider, 'dataset' => $dataset], icon: 'tabler:map-2');
   ```

**Before wiring up any menu item that links to a route carrying an entity identity, check the entity is on the modern pattern** (`#[RouteIdentity]` + `RouteIdentityTrait`, not a hand-written `getRp()` or the deprecated core-bundle interface) — a menu item is exactly the kind of call site that silently keeps working on the old pattern until the route itself changes shape, at which point every entity-only call site is unaffected and every hand-built-array call site breaks at once.

99% of the time, the entity a menu listener needs is already sitting in `$event->getOption(...)` because some base template further up the inheritance chain declared it. If `getOption()` comes back null/wrong, the fix is almost always a missing `tabler_menu_options({...}, _self)` call (or a missing key in `survos_tabler.yaml`) in the base template that page extends — not a new lookup inside the menu listener.

**Deliberate exception: per-row hit lists (search results, bookmarks).** A search-hit or bookmark row is a plain projection (e.g. a `dataset`/`localId` string pair off a Meilisearch document or a bookmark table row), not an entity — fetching the real `Tenant`/`Folio` entity for every row on a results page would be an N+1 query per page render, unlike the small, bounded loops (home page archive lists, menu items) where fetching one entity per item is cheap. It's fine for these to keep building route params from the raw fields directly, **as long as the target route's own param shape is stable** (an openfoto-only route like `tenant_photo_show` with `{tenantCode}`/`{localId}`, not a `survos_folio_*` bundle route that can migrate shape under you). If a hit-list page ever needs this to be entity-driven, batch-fetch: collect the distinct codes across the page once, `findBy(['code' => $codes])`, index into a map, then look up per row — not a `find()` per row.

### Entity injection in controllers — type-hint the entity, never fetch it yourself

`#[RouteIdentity]` drives both directions of the URL round-trip. `getRp()` generates the route parameters, and field-bundle's `RouteIdentityValueResolver` resolves them back: it sees a typed entity argument, reads the entity's `#[RouteIdentity]` attribute, and runs `findOneBy([field => value])` — throwing a 404 when a non-nullable argument can't be found.

The route parameter key is `lcfirst(shortName) . 'Id'` — `Component` → `componentId`, `Site` → `siteId` — unless overridden with `#[RouteIdentity(field: 'code', key: 'slug')]`. `getRp()` emits exactly the same keys, so `path()` and the resolver always agree.

```php
#[Route('/component/{componentId}', name: 'component_show')]
public function show(Component $component): Response   // resolved by componentId → code lookup
```

```php
// WRONG — never accept the raw id and query the repository yourself
#[Route('/component/{componentId}', name: 'component_show')]
public function show(string $componentId, ComponentRepository $repo): Response
{
    $component = $repo->findOneBy(['code' => $componentId]); // boilerplate the resolver already does
```

Also wrong: repeating `#[MapEntity(mapping: ['componentId' => 'code'])]` on every action — that's the boilerplate `RouteIdentity` exists to eliminate.

**Composite identity (parent chains).** An entity scoped by a parent declares it once; `getRp()` walks the chain automatically:

```php
#[RouteIdentity(field: 'code', parents: ['tenant'])]
final class Project implements RouteParametersInterface { ... }

// $project->getRp() → ['tenantId' => 'acme', 'projectId' => 'photo-archive']
// {{ path('project_show', project.rp) }} — no manual merge with tenant.rp
```

One current limitation: `RouteIdentityValueResolver` does not yet resolve parent chains on the inbound side, so a controller for a compound-identity entity still needs an explicit `#[MapEntity]` (see the `@todo` in the resolver). Everything else — single-field identity, non-`id` fields like `code`, custom keys — injects directly.

## Database

### Local default: the shared Docker Postgres

Apps default to the one shared, long-running Postgres container defined in
`~/sites/docker/docker-compose.yaml` (`survos_postgres`, a TimescaleDB image, port 5434,
`postgres`/`docker` credentials) — not a per-app database. Each app just picks its own database
name inside that one instance:

    DATABASE_URL=postgresql://postgres:docker@127.0.0.1:5434/<dbname>?serverVersion=18&charset=utf8

See `ssai`, `kpa`, `md`'s `.env` for real examples — this line is active (uncommented) in `.env`,
with a commented-out SQLite alternative left beneath it for quick disposable-DB work (see below).

A per-app `compose.yaml`/`compose.override.yaml` from the `doctrine/doctrine-bundle` Flex recipe
(its own throwaway Postgres container, generic `app`/`!ChangeMe!` credentials, a random host
port) is Flex's generic scaffold default, not this platform's convention — a new app should have
its `DATABASE_URL` in `.env` swapped to the shared-instance form above, and the recipe's own
`compose.yaml` service can be left unused or removed. A `DATABASE_URL` still pointing at
`app:!ChangeMe!@127.0.0.1:<random-port>` is a sign an app never got switched over.

There's also a separate shared `postgres_messenger` container (port 5435) for Symfony Messenger's
Doctrine transport, and shared Meilisearch/Redis/Mercure/Mailpit/RabbitMQ containers in the same
compose file — same idea, one shared instance rather than one per app.

### Production database

Never hardcode a production `DATABASE_URL` into `.env` (tracked, shared). To point locally at
production data, in order of preference:

1. `.env.local` (gitignored) — a commented-out production `DATABASE_URL` line, uncommented only
   while you need it, not left active.
2. `dokku config:get <app> DATABASE_URL` on the production host.
3. The team password vault.

### Schema changes

Doctrine DBAL configs for PostgreSQL projects should exclude extension-owned TimescaleDB schemas from schema diffs and migrations:

    doctrine:
        dbal:
            schema_filter: '~^(?!nglayouts_)(?!_timescaledb_)(?!timescaledb_)(?!toolkit_experimental)~'

For multi-connection configs, put the same `schema_filter` on the `default` connection. Keep any existing project-specific exclusions in the same negative lookahead, such as `messenger_messages`.

When entity changes require a schema update:

1. Check `DATABASE_URL` in `.env.local` (or `.env` if unset there).
2. **SQLite** → run `php bin/console doctrine:schema:update --force` directly. No migration file needed — SQLite is dev-only, data is disposable. If a change can't apply cleanly in place (e.g. a new `NOT NULL` column with no default), it's fine to drop the SQLite file and reload/reseed rather than hand-craft a migration for a throwaway database.
3. **PostgreSQL (or any other)** → stop and ask the developer to review and run the migration. Generate the diff with `php bin/console doctrine:migrations:diff` but do not run it automatically.

Rationale: migrations are an audit trail for shared/production databases. SQLite dev databases are throwaway — a migration file is unnecessary friction.

### Platform S3 mount (rclone) — `/platform/folio-archive`

`zm`'s `folio_archive.storage` (see `config/packages/flysystem.yaml`) is a plain Flysystem `local`
adapter pointed at `%env(APP_DATA_DIR)%/folio-archive` (`APP_DATA_DIR=/platform`). That path is
**not real local disk** — it's a symlink (`/platform/folio-archive -> ~/platform-s3/folio-archive`)
into an **rclone FUSE mount** of the shared S3 bucket `survos-platform` (Hetzner Object Storage,
`folio-archive/` prefix). This is deliberate: `FolioArchiveService` and `folio:pull` get to use
plain PHP file calls (no Flysystem remote-fetch/readStream-to-tempfile dance) while rclone's own
VFS cache handles "only re-fetch from S3 if stale" underneath. Every dev machine and every deploy
target reads/writes the same S3 location this way.

**This mount does not persist across a reboot on its own** — it's a FUSE process, not a real
mount table entry. Every dev machine (including new ones — Praveen, the MacBook setup) needs it
running as a **service**, or `/platform/folio-archive` silently goes empty/dangling and anything
touching folio archives throws `Unable to create a directory at /platform/folio-archive` (Flysystem
trying to lazily create a root that's actually an occupied-but-broken symlink target).

**Prerequisites (any OS):**
1. `rclone` installed (`~/.local/bin/rclone` here).
2. An rclone remote named `hetzner-museado` pointing at Hetzner Object Storage
   (`endpoint = https://fsn1.your-objectstorage.com`, `provider = Other`, S3 type). **Get the
   access key/secret from Tac or the team vault — never commit them to a tracked file.** Same
   credentials as `ssai`'s `AWS_S3_ACCESS_ID`/`AWS_S3_ACCESS_SECRET` (`aws configure --profile
   hetzner` also works, for `aws s3 ls` debugging — same key pair, config lives in `~/.aws/config`).
3. Local mount point `~/platform-s3` (empty dir, created automatically by the service below).

**Linux — systemd user service** (`~/.config/systemd/user/rclone-platform-s3.service`):

```ini
[Unit]
Description=rclone mount of survos-platform S3 bucket (folio-archive backing store)
After=network-online.target
Wants=network-online.target

[Service]
Type=notify
ExecStartPre=/bin/mkdir -p %h/platform-s3
ExecStart=/home/tac/.local/bin/rclone mount hetzner-museado:survos-platform %h/platform-s3 \
    --vfs-cache-mode=writes \
    --dir-cache-time=1h
ExecStop=/bin/fusermount -u %h/platform-s3
Restart=on-failure
RestartSec=5

[Install]
WantedBy=default.target
```

```bash
loginctl enable-linger "$USER"   # starts the service at boot even with no login session
systemctl --user daemon-reload
systemctl --user enable --now rclone-platform-s3.service
systemctl --user status rclone-platform-s3.service   # confirm "active (running)"
```

**macOS — launchd (MacBook dev setup):** systemd doesn't exist on macOS; use a
`~/Library/LaunchAgents/com.survos.rclone-platform-s3.plist` `LaunchAgent` instead, same
`rclone mount hetzner-museado:survos-platform ~/platform-s3 --vfs-cache-mode=writes` command as
`ProgramArguments`, `RunAtLoad`/`KeepAlive` true, loaded via `launchctl load -w`. Requires macFUSE
installed first (`brew install --cask macfuse`, needs a one-time System Settings security approval
after install — the FUSE kernel extension won't load otherwise). Not yet turned into a checked-in
plist template — do that the first time someone actually sets up a MacBook, rather than writing an
untested one now.

**Sanity check after any setup:** `ls /platform/folio-archive` should show real content
(`folio/`, `fpeu/`, etc.), not "No such file or directory."

## Reverse proxy / trusted_proxies

Every app deploys behind the dokku/Docker reverse proxy (TLS terminated upstream, plain HTTP to
the container). `config/packages/framework.yaml` needs to trust it, or Symfony ignores
`X-Forwarded-Proto: https` and builds `http://` absolute URLs — broken cross-origin checks,
insecure-looking QR codes/emails/redirects, anything using `generateUrl(..., ABSOLUTE_URL)`. `zm`,
`md`, `pgsc` already have this; `rut` and `dadjokes` didn't (found via rutado's homepage QR code
silently encoding an `http://` URL) — check for it in any app before assuming absolute URLs are
correct:

    parameters:
        default_trusted_proxies: 'private_ranges'

    framework:
        trusted_proxies: '%env(default:default_trusted_proxies:TRUSTED_PROXIES)%'
        trusted_headers: ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-forwarded-port']

## Configuration

- Config that benefits from types, path logic, or conditionals is PHP, not YAML. YAML stays for static lists with no logic.
- PHP config files return a typed value object (e.g. `return new Sources(new Source(...), ...)`).

## composer.json `extra` for tool-specific config

When a Survos tool reads per-package configuration, it uses `composer.json`'s `extra` key with the convention:

- **One key per tool, kebab-case, tool-named.** `extra.field-bundle`, `extra.desc-sync`, `extra.site-monitor`. Never claim a generic name like `extra.config`.
- **Object-under-key**, never bare values. Forward-compatible when settings are added later.
- **Defaults belong to the tool, not the file.** `composer.json` contains only overrides. Absent key means "use defaults." This makes opt-out work cleanly — tools default to enabled, packages opt out explicitly when they need to.
- **Document the schema** in the tool's README. Tools that read `extra` should publish their supported keys with defaults and examples.

Example consumer pattern:

```php
$extra = $composerData['extra']['my-tool'] ?? [];
$enabled = $extra['enabled'] ?? true;  // default enabled (opt-out, not opt-in)
```

## Tables and grids

- **Under ~1000 rows**: use `survos/simple-datatables-bundle`. Client-side sorting/filtering, zero server round-trips, minimal setup.
  `field-bundle` exposes every `#[EntityMeta]`-annotated entity as a Twig global: `App\Entity\Component` → `APP_ENTITY_COMPONENT`, `App\Entity\Site` → `APP_ENTITY_SITE`, etc. (screaming snake of the short class name). Use these globals — never hardcode escaped FQCNs in templates.
  ```twig
  {# columns auto-derived from #[Field] attributes once survos/mono#4 lands #}
  <twig:simple_datatables :entityClass="APP_ENTITY_COMPONENT" :data="components" perPage="25" />

  {# until then, define columns explicitly #}
  {% set columns = ['code', 'composerName', 'kind', 'minimumStability'] %}
  <twig:simple_datatables :columns="columns" :data="components" perPage="25" />
  ```
  Public constants are also exposed: `APP_ENTITY_COMPONENT_SOME_CONST`.
- **Over ~1000 rows, or needs server-side filtering/faceting**: use `survos/api-grid-bundle` backed by Meilisearch or API Platform.
- Never use api-grid for readonly admin/status views that fit on one page — simple-datatables is the right tool.

## Stack

- AssetMapper, not Webpack/Encore.
- `asset-map:compile` is fine in dev to confirm something compiles, but delete `public/assets/` (`rm -rf public/assets` + cache clear) the moment you're done checking. Its mere presence makes AssetMapper serve those frozen static files instead of dynamically compiling from live source on each request — so any subsequent edit to a controller/asset silently goes stale with no error. `public/assets/` is gitignored and prod-build-only; it should never linger in a dev checkout.
- Tabler UI.
- EasyAdmin 4.
- API Platform 4.
- Castor for task running.
- FrankenPHP/Caddy on Dokku/Hetzner for deployment.

## Survos bundle ecosystem (use these, don't reinvent)

- `survos/field-bundle` — entity field metadata, accessors.
- `survos/dataset-bundle` — shared dataset concepts.
- `survos/folio-bundle` — sqlite-backed normalized data.
- `survos/media-bundle` — media management.
- `survos/lingua-bundle` — translation memory.
- `survos/jsonl-bundle` — JSONL ingestion/export.
- `survos/meili-bundle` — Meilisearch integration.
- `survos/import-bundle` — generic import workflows.
- `survos/state-bundle` — state machines.
- `survos/ez-bundle` — EasyAdmin wrapper.
- `survos/deployment-bundle` — Dokku deployment helpers.
- `survos/ark-bundle` — ARK identifiers (in development).
- `survos/iiif-bundle` — IIIF (in development).
- `survos/ciine-bundle` — asciinema rendering (extraction in progress).

## Meilisearch frontend search UI

Full reference: `survos/meili-bundle`'s `docs/frontend-instant-search.md` (read
before writing any search-page JS — multiple apps have independently
hand-rolled a bespoke controller and hit the same bugs).

- **Use `@survos/meili-bundle/insta`, never a bespoke Stimulus controller.**
  Search box, facets, hits, pagination, bookmarkable routing (`routing: true`
  by default), and hybrid/semantic search are already built and
  config-driven via Stimulus values. Writing `instantsearch({...})` by hand
  in an app is a sign you're duplicating this controller.
- **Facets are `data-attribute`/`data-widget` nodes** inside one
  `refinementList` target — not individual Stimulus targets per facet.
- **Hit templates are real `templates/js/{indexBase}.html.twig` files**,
  loaded client-side via `templateUrl` + `loadTemplateFromUrl()` (resolved
  by `TemplateController`'s `/meili/template/{name}` route). Never a JS
  template-literal string.
- **Two stylesheets are required and neither auto-imports:**
  1. `instantsearch.css`'s algolia theme — import it from a dedicated
     entrypoint that imports your normal `app.js` first (so Tabler/Bootstrap
     registers before algolia's component resets do), not from `app.js`
     directly.
  2. `bu/meili-bundle/public/meili.css` — a **plain bundle public asset**
     (`assets:install`, not AssetMapper). Link it explicitly:
     `<link rel="stylesheet" href="{{ asset('bundles/survosmeili/meili.css') }}">`.
     Forgetting this is the most common failure: the page works, but
     refinement lists/pagination render as an unstyled bullet list.
- **`MEILI_PREFIX` must match across apps that share an index** — one app
  building an index and a different app serving search over it need
  identical prefixes, or `IndexNameResolver::uidForRaw()` computes two
  different physical index names and the serving app silently sees nothing.

## GitHub workflow

- Issues are the cross-repo work queue. Reference issue numbers in commits and in agent chats.
- Labels for cross-cutting taxonomy: `repo:<name>`, `bundle:<name>`, `layer:1|2|3`, `refactor`, `extract`, `convention-violation`.
- Pan-repo GitHub Project board aggregates open issues across Survos/Museado repos.
