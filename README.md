# ereborcodeforge/durin-core

## Purpose

Project model and deterministic tooling primitives for Durin applications.

## What this package owns

- Project discovery and path resolution (runtime-neutral project model)
- `ProjectOptions`, `RuntimeIntent`, `ResolvedRuntime`, `DurinManifest`
- `durin.yaml` manifest parsing, serialization, and legacy manifest normalization
- Scaffold plan model and safe filesystem mutation (`ScaffoldWriter`)
- Preset contracts (`Preset`, `PresetRegistry`) — not the preset catalog
- Minimal console output abstractions used by mutation

## What this package does not own

- Runtime resolution, runtime registry, or runtime provisioning
- Eregion installation/configuration, preset resolution, broker execution, process supervision
- Concrete architecture presets or templates
- Architecture detection, drift, adopt/evolve/migrate planning
- Durin Forge CLI commands (Forge owns `RuntimeResolver` and applies `ResolvedRuntime` to the manifest)
- Terminal rendering / UX
- Doctor, status, graph, optimize, serve/dev orchestration

## Runtime flow (Forge + Core)

```text
Preset → RuntimeIntent → manifest (unresolved) → Forge RuntimeResolver → ResolvedRuntime → Core serializes final manifest
```

Core never chooses engine, execution, or supervisor. See `docs/durin-core-runtime-model-spec.md` (status: **implemented**).

## Installation

```bash
composer require ereborcodeforge/durin-core
```

During local multi-package development (path repository):

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../durin-core",
      "options": { "symlink": true }
    }
  ]
}
```

## PHP requirement

PHP `^8.5`

## Basic usage

```php
use EreborCodeForge\Durin\Core\Project\ProjectDiscovery;
use EreborCodeForge\Durin\Core\Mutation\ScaffoldWriter;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

$project = (new ProjectDiscovery())->discover(__DIR__);

$plan = (new ScaffoldPlan())
    ->directory('src')
    ->file('src/.gitkeep', '');

$result = (new ScaffoldWriter())->write($project->root(), $plan);
```

## Dependency direction

```text
durin-core
  -> PHP only (no Durin packages)
```

Nothing in this package may depend on `durin-presets`, `durin-architecture`, or Durin Forge.

## Supported API

Stable surface for consumers:

- `Project`, `ProjectDiscovery`, `ProjectPaths`
- `DurinManifest`, `DurinManifestParser`
- `Runtime\RuntimeIntent`, `Runtime\ResolvedRuntime`
- `ScaffoldPlan`, `ScaffoldAction`, `ScaffoldWriter`, `ScaffoldWriteResult`
- `Contract\Preset`, `Contract\ProjectOptions`, `Contract\PresetRegistry`

`ProjectOptions` carries an optional `RuntimeIntent` (mode + capabilities). `DurinManifest` stores either unresolved runtime (`state: unresolved`) or a `ResolvedRuntime` (mode, engine, execution, optional supervisor). Core never chooses a concrete runner.

`isJobMode()` uses `runtime.mode === 'job'` when runtime is resolved; it never inspects preset IDs. Unresolved manifests may use a features-based heuristic only until Forge resolves runtime.

The `engine` field is an opaque string (often `mithril` today); Core does not assume a specific engine.

## Versioning status

**0.2.0+** — runtime-neutral manifest model (`RuntimeIntent`, `ResolvedRuntime`, unresolved state, legacy `server:` normalization). **0.1.0** was the initial extraction release.

## Relationship to Durin Forge

Durin Forge remains the composition root and CLI. This package supplies the shared project/tooling primitives Forge (and sibling packages) consume through Composer.
