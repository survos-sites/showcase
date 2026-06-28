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

Every entity that appears in routes **must** implement `RouteParametersInterface` and use `RouteIdentityTrait`. Note the intentional split: the **interface** remains in `survos/core-bundle` (not yet migrated), the **trait** lives in `survos/field-bundle`. Use both:

```php
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\FieldBundle\Attribute\RouteIdentity;
use Survos\FieldBundle\Entity\RouteIdentityTrait;

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

If field-bundle lacks a needed capability, flag it as a field-bundle issue. Don't work around silently.

## Database schema changes

Doctrine DBAL configs for PostgreSQL projects should exclude extension-owned TimescaleDB schemas from schema diffs and migrations:

    doctrine:
        dbal:
            schema_filter: '~^(?!nglayouts_)(?!_timescaledb_)(?!timescaledb_)(?!toolkit_experimental)~'

For multi-connection configs, put the same `schema_filter` on the `default` connection. Keep any existing project-specific exclusions in the same negative lookahead, such as `messenger_messages`.

When entity changes require a schema update:

1. Check `DATABASE_URL` in `.env.local`.
2. **SQLite** → run `php bin/console doctrine:schema:update --force` directly. No migration file needed — SQLite is dev-only, data is disposable.
3. **PostgreSQL (or any other)** → stop and ask the developer to review and run the migration. Generate the diff with `php bin/console doctrine:migrations:diff` but do not run it automatically.

Rationale: migrations are an audit trail for shared/production databases. SQLite dev databases are throwaway — a migration file is unnecessary friction.

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

## GitHub workflow

- Issues are the cross-repo work queue. Reference issue numbers in commits and in agent chats.
- Labels for cross-cutting taxonomy: `repo:<name>`, `bundle:<name>`, `layer:1|2|3`, `refactor`, `extract`, `convention-violation`.
- Pan-repo GitHub Project board aggregates open issues across Survos/Museado repos.
