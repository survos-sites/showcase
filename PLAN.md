# PLAN.md — survos-sites/showcase

Current and near-term work. Append decision records as work progresses.

## Status

Bootstrapping platform conventions. About to refactor the loader into a clean foundation that handles repos and bundles uniformly and ingests per-component `OVERVIEW.md` / `PLAN.md`.

## Active: Phase 2 — Loader refactor

**Goal:** Replace `src/Command/LoadDataCommand.php` with `src/Service/AppService.php`, a single service class exposing multiple `#[AsCommand]` methods sharing private helpers.

### Target shape

```php
namespace App\Service;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AppService
{
    public function __construct(
        private ComponentRepository $componentRepository,
        private EntityManagerInterface $em,
        private Sources $sources,
    ) {}

    #[AsCommand('app:load', 'load survos components from local sources')]
    public function load(SymfonyStyle $io, #[MapInput] LoadInput $input): int
    {
        // iterates $this->sources, calls $this->loadComponent()
    }

    #[AsCommand('app:update', 'run composer/server updates on loaded components')]
    public function update(SymfonyStyle $io, #[MapInput] UpdateInput $input): int
    {
        // uses shared helpers via $this
    }

    private function loadComponent(string $dir, ComponentKind $kind, SymfonyStyle $io): ?Component { /* ... */ }
    private function updateAppJson(Component $c, string $path): void { /* ... */ }
    private function updateComposerJson(Component $c, string $path): void { /* ... */ }
    private function updateGitConfig(Component $c, string $path): void { /* ... */ }
    private function updatePwaYaml(Component $c, string $path): void { /* ... */ }
    private function updateOverview(Component $c, string $path): void { /* ... */ }
    private function updatePlan(Component $c, string $path): void { /* ... */ }
}
```

`LoadInput` and `UpdateInput` are MapInput DTOs in `src/Input/`. Even if they start with one or two fields, the DTO is the convention — adding flags later doesn't require refactoring the command signature.

### Tasks (in order)

1. ✅ Create `App\Enum\ComponentKind` — cases map directly to Composer `type`: `Bundle = 'symfony-bundle'`, `Library = 'library'`, `App = 'project'`, `Plugin = 'composer-plugin'`.
2. Rename entity `Project` → `Component`. PHP 8.4 style: public properties, `readonly` PK, property hooks for computed fields, `#[EntityMeta]`/`#[Field]`/`#[RouteIdentity]` from field-bundle. No getters/setters. Merge task 3 into this.
3. ~~Convert `Component` to use field-bundle attributes~~ — merged into task 2.
4. Add fields to `Component`: `kind: ?ComponentKind`, `overview: ?string` (text), `plan: ?string` (text).
5. Create `App\Entity\Site` — the deployed instance of an `App`-kind component. PK: dokku app name (e.g. `lingua`). Fields: `dokkuHost`, `localPort` (from Symfony proxy, transient), `screenshotPath`, `ManyToOne → Component`. Computed hooks: `productionUrl`, `localUrl`, `isRunning`. Rename `ProjectRepository` → `ComponentRepository`, add `SiteRepository`.
6. Generate Doctrine migration. **Show diff before running.**
7. Create `config/sources.php` returning a `Sources` readonly value object. `Source { path, kind, depth }`. Wire as a service.
8. Create `src/Input/LoadInput.php` and `src/Input/UpdateInput.php` MapInput DTOs.
9. Create `src/Service/AppService.php` with `app:load` and `app:update` methods. `app:load` reads `.git/config` for dokku remote (Site PK + host), calls `SurvosUtils::getSymfonyProxySites()` for `localPort`, creates/updates both `Component` and `Site` in one pass.
10. Delete `src/Command/LoadDataCommand.php`.
11. Run `bin/console app:load` against the local Survos monorepo. Show output. Confirm idempotency by running twice.

### Conventions for this refactor (reminders)

- Method-level `#[AsCommand]` on `AppService` methods. One class, one constructor, shared private helpers. Do not extract `LoadCommand` / `UpdateCommand` as separate classes.
- Use `#[MapInput]` DTOs for command inputs even when they currently have only one or two fields — establishes the pattern, makes future flags additive.
- `#[Argument('desc')]` / `#[Option('desc')]` on DTO properties, positional description, no `description:` named param.
- Return `Command::SUCCESS` / `Command::FAILURE`.
- No `dump()` / `dd()`. Verbose output gated on `$io->isVerbose()`.
- `app:load` is read-only against the filesystem. All composer/server mutation lives in `app:update`. They never call each other.

### composer.json `extra` for description sync (future)

Once `AppService` is in place, a follow-up task will use `composer.json#extra.showcase` (or similar) to control per-component sync behavior — e.g., whether showcase should overwrite `composer.json#description` from a canonical source, or which GitHub repo setting to push it to. The pattern is documented in `CONVENTIONS.md`. Not in this PR.

### Out of scope for this PR

- Extracting ciine logic to `survos/ciine-bundle` (file as GitHub issue).
- Adding `app:status` command (leave room in `AppService`; don't implement).
- Pan-repo GitHub Project board.
- Top-level `CONVENTIONS.md` distribution to other repos (separate effort).
- `survos/site-monitor-demo` standalone repo for the article series.

### Decisions log

- **2026-05-17 — Method-level `#[AsCommand]`.** Adopted Symfony 8.1's method-level `#[AsCommand]` instead of one-class-per-command. Cohesive command families share a single constructor and private helpers via `$this`. Rule: split classes when helpers stop being shared across methods, not before. Worth a Medium article series — see backlog.
- **2026-05-17 — Positional descriptions everywhere.** `#[AsCommand]`, `#[Argument]`, `#[Option]` all take description as the positional argument. Drop named-parameter syntax. Consistent across the codebase, easier to teach.
- **2026-05-17 — Service-named classes, not Command-named.** `AppService`, not `AppCommands`. The CLI is one transport among many into the service layer. Class names reflect the primary identity.
- **2026-05-17 — Symlinks rejected for OVERVIEW/PLAN.** OVERVIEW.md and PLAN.md stay canonical in each repo/bundle. Showcase reads them; doesn't host them. Reasoning: git symlinks break across separate clones and confuse agents.
- **2026-05-17 — PHP over YAML for `sources` config.** Needs path logic (`dirname`, `realpath`, conditional `file_exists`) and types. Returning a typed value object beats parsing+validating YAML.
- **2026-05-17 — `AppService` in `src/Service/`.** It's a service that happens to expose commands. The location reflects the architectural primary; `#[AsCommand]` autodiscovery finds it regardless.
- **2026-05-17 — MapInput DTOs for all command inputs, even single-field ones.** Establishes the pattern. Descriptions and validation live in one place (the DTO), command signatures stay small, and adding flags later doesn't require touching command signatures.
- **2026-05-17 — composer.json `extra` convention.** One kebab-case key per tool, object-under-key, defaults belong to the tool, document the schema. See CONVENTIONS.md.
- **2026-05-17 — ComponentKind maps to Composer `type`.** Cases: `Bundle = 'symfony-bundle'`, `Library = 'library'`, `App = 'project'`, `Plugin = 'composer-plugin'`. Enum backed by the Composer type string so `ComponentKind::from($composerJson['type'])` works directly. 86 bundles in `mono/bu`, 4 libraries in `mono/lib`, 82 apps in `sites/`.
- **2026-05-17 — Component PK is a slug derived from composer name.** Format: replace `/` with `__` (double underscore). Example: `survos/jsonl-bundle` → `survos__jsonl-bundle`. Dot rejected (invalid in Meilisearch). Losslessly reversible. `composerName` stored as a separate plain string field. `localCode` stores the directory basename (e.g. `lingua`) for `.wip` URL generation until the Site entity exists.
- **2026-05-17 — Site entity added to Phase 2 (not deferred).** A `Component` is the codebase; a `Site` is its deployed instance. PK = dokku app name parsed from `remote.dokku.url` in `.git/config` (e.g. `lingua` from `dokku@dokku.survos.com:lingua`). `localPort` populated transiently from `SurvosUtils::getSymfonyProxySites()` (Symfony proxy at `:7080`) on each `app:load` run. `localCode` removed from `Component` — it was always `Site` data. Future: `app:start` can iterate `Site` records and call `symfony server:start --dir=$component->localDir` to bring all sites up locally for 8.1 testing.

## Backlog (file as GitHub issues)

- **Extract asciinema/ciine logic into `survos/ciine-bundle`.** Showcase historically rendered asciinema casts. That logic predates ciine-bundle and should live there. Scope: identify ciine-related controllers, templates, services, assets in showcase (grep `asciinema`, `cast`, `ciine`); move to `survos/ciine-bundle`; expose via bundle config/routes; require bundle from showcase; remove from showcase; verify rendering. Labels: `repo:showcase`, `bundle:ciine`, `refactor`, `extract`.
- **Pan-repo GitHub Project board.** Aggregates open issues across Survos/Museado repos with shared labels (`repo:*`, `bundle:*`, `layer:1|2|3`).
- **`app:status` command on `AppService`.** Once load+update settle, add a status method that reports per-component: last loaded, git status, deploy status.
- **`app:desc:sync` family on `AppService`.** Reads `composer.json#description` per component and pushes to GitHub repo settings and `app.json`. Uses `composer.json#extra.showcase` for opt-out and per-target control.
- **Issue templates per repo.** Standardize "which bundles does this touch / layer / migration needed" prompts.
- **`work-on <name>` shell helper.** Pre-loads platform + component context into an agent's session.
- **`survos/site-monitor-demo` standalone repo.** Supports the four-article Medium series on method-level `#[AsCommand]`, value resolvers, MapInput DTOs, and bundle packaging.

## Medium article series (planned)

Four-article series demonstrating the patterns this repo uses. Built against a tiny standalone demo: `survos/site-monitor-demo`.

1. **Call Your Symfony Services from the Console** — method-level `#[AsCommand]`, one service class, seven commands (`site:add`, `remove`, `enable`, `disable`, `show`, `list`, `scan`). String inputs. Tag: `v1-strings`.
2. **Typed Arguments with Custom Value Resolvers** — refactor to `SiteUrl` value object + custom `ValueResolverInterface`. Validation via Symfony Validator (`#[Assert\Url]`). Tag: `v2-resolvers`.
3. **Grouping Inputs with `#[MapInput]` DTOs** — refactor `list` and `scan` to use input DTOs. Composition (`ApplyInput contains ScanInput`). Tag: `v3-mapinput`.
4. **Packaging It as a Reusable Bundle** — extract `SiteService` into `site-monitor-bundle`. Bundle configuration, service registration, command autodiscovery from a bundle. Tag: `v4-bundle`.

Persistence: Sqlite + Doctrine. README supports both `doctrine:migrations:migrate` (migration committed) and `doctrine:schema:create` (simple path). Drafted outline-first, then code-first, then prose against working code.
