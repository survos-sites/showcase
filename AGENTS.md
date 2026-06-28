# AGENTS.md — survos-sites/showcase

This repo is the **platform index/portal** for the Survos/Museado ecosystem. It scans sibling repos and bundles, ingests their metadata, and presents pan-repo information (overviews, plans, live site links, status).

## Read first

1. `CONVENTIONS.md` in this repo — platform-wide rules. **Non-negotiable.**
2. `PLAN.md` in this repo — current work, near-term roadmap, decisions.
3. The specific file(s) you're about to touch.

## What this repo is

- Symfony 8.1 application.
- Aggregates metadata across the Survos/Museado ecosystem (repos like `ssai`, `harvest`, `md`, `zm`, `lingua`, `media`, `ai-tools`; bundles in the monorepo).
- Source of truth pointer — showcase reads each component's canonical `OVERVIEW.md` and `PLAN.md` rather than centralizing them. Each repo and bundle owns its own docs.

## What this repo is not

- Not the canonical location for any repo's or bundle's documentation. Those live with the code.
- Not a code generator or scaffolder.
- Not where pan-repo GitHub build status lives (separate repo).

## Architecture at a glance

- `LoadDataCommand` scans local sibling directories, parses `app.json`, `composer.json`, `.git/config`, `config/packages/pwa.yaml` per repo, persists into `Project` entity.
- Phase 2 refactor (see `PLAN.md`): generalize to `Component` (kinds: `Repo`, `Bundle`, `Site`), ingest `OVERVIEW.md` and `PLAN.md`, drive scan from PHP config (`config/sources.php`), expose load and update as methods on `AppService`.

## Companion work informing these conventions

A four-article Medium series is being drafted that demonstrates the patterns this repo uses. The series builds a tiny demo repo (`survos/site-monitor-demo`) progressively:

1. **Call Your Symfony Services from the Console** — method-level `#[AsCommand]`, one service class, seven commands.
2. **Typed Arguments with Custom Value Resolvers** — refactor to `SiteUrl` value object + resolver.
3. **Grouping Inputs with `#[MapInput]` DTOs** — refactor to typed input DTOs for `list` and `scan`.
4. **Packaging It as a Reusable Bundle** — extract into `site-monitor-bundle`.

When the demo repo exists, its `v3-mapinput` state is the reference implementation for the conventions in this file. Agents should check that repo when convention questions arise. (Note: as of bootstrap, the demo repo doesn't exist yet — flag if asked to reference it.)

## Working with agents

- Default working directory is showcase root.
- For multi-repo work, agent should `cd` into the target repo and read *its* `AGENTS.md` + showcase's `CONVENTIONS.md`.
- File changes stay scoped to the active PR. No drive-by refactors.
- When a convention conflict is found in existing code, flag it. Don't silently rewrite.

## Out-of-scope work tracked elsewhere

- Extract asciinema/ciine logic from showcase into `survos/ciine-bundle` (GitHub issue, not in current PR).
- Pan-repo GitHub Project board setup.
- ScanStationAI, harvest, md, zm work — those are their own repos with their own `AGENTS.md`.
- `site-monitor-demo` standalone repo — separate project supporting the article series.

## Contact

Tac Tacelosky — tac@museado.org

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: symfony/ai-mate.
<!-- END AI_MATE_INSTRUCTIONS -->
