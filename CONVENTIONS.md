# Survos/Museado Conventions

Platform-wide conventions for any repo in the Survos/Museado ecosystem. When working in a specific repo, also read that repo's `AGENTS.md` and `PLAN.md`.

## Symfony commands — the canonical pattern

Commands live as **methods on a service class**, not as standalone command classes. Method-level `#[AsCommand]` (Symfony 8.1+) lets one service class expose a whole family of related commands sharing a single constructor and private helpers.

### Rules

- `#[AsCommand('name', 'description')]` on **methods**. Positional description (second argument), not the `description:` named parameter. Same convention as `#[Argument]` and `#[Option]`.
- **Never `extends Command`.** Import `Symfony\Component\Console\Command\Command` only for the return constants.
- Return `Command::SUCCESS` / `Command::FAILURE` / `Command::INVALID`.
- `#[Argument('desc')]` and `#[Option('desc')]` — positional description string. Never `description:` named param.
- First parameter is typically `SymfonyStyle $io` (use when you want its helpers — `success`, `table`, `progressBar`) or `OutputInterface $output` (leaner). Pick per method.
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

## Entities

- No getter/setter boilerplate. Use `survos/field-bundle` attributes for accessors and metadata.
- If field-bundle lacks a needed capability, flag it as a field-bundle issue. Don't work around silently.
- ULID for primary keys across Doctrine, API Platform, Meilisearch, S3. Path format: `{tenant}/{dataset}/{ulid}/{variant}`.

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
