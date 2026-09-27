# ereborcodeforge/durin-core

## Purpose

Project model and deterministic tooling primitives for Durin applications.

## What this package owns

- Project discovery and path resolution
- `durin.yaml` manifest model, parser, writer helpers
- Scaffold plan model and safe filesystem mutation (`ScaffoldWriter`)
- Preset contracts (`Preset`, `PresetRegistry`, `ProjectOptions`)
- Minimal console output abstractions used by mutation

## What this package does not own

- Concrete architecture presets or templates
- Architecture detection, drift, adopt/evolve/migrate planning
- Durin Forge CLI commands
- Terminal rendering / UX
- MithrilPHP or Eregion runtime integration
- Doctor, status, graph, optimize, serve/dev orchestration

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
- `ScaffoldPlan`, `ScaffoldAction`, `ScaffoldWriter`, `ScaffoldWriteResult`
- `Contract\Preset`, `Contract\ProjectOptions`, `Contract\PresetRegistry`

## Versioning status

Initial extraction release: **0.1.0** (pre-1.0; APIs may still evolve with Durin Forge integration).

## Relationship to Durin Forge

Durin Forge remains the composition root and CLI. This package supplies the shared project/tooling primitives Forge (and sibling packages) consume through Composer.
