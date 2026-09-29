# Durin Core — Runtime-Neutral Manifest & Project Model

**Status:** DONE / implemented (`ereborcodeforge/durin-core` v0.2.0+)  
**Repository:** `EreborCodeForge/durin-core`

# Mission

O `durin-core` é **runtime-neutral**: modela intenção e estado de runtime no manifest e nos DTOs, mas **não participa da resolução de runtime**. Escolha de engine, execution, supervisor e provisioning pertence ao **Durins Forge** (e pacotes de runtime), não ao Core.

Core pode representar:

```text
runtime mode (intent ou resolvido)
runtime unresolved
resolved execution runtime
optional supervisor (valor opaco — Core não decide qual usar)
features
manifest state
```

Core **não** decide runtime, **não** registra runners e **não** assume Eregion.

# Fluxo atual (Preset → manifest final)

```text
Preset (durin-presets)
  ↓
RuntimeIntent (ProjectOptions / scaffold)
  ↓
durin-core manifest = unresolved
  ↓
Forge RuntimeResolver
  ↓
ResolvedRuntime
  ↓
durin-core serializes final manifest (canonical YAML)
```

O Core **persiste** o resultado da resolução quando o Forge (ou outro composition root) aplica `ResolvedRuntime` ao projeto; o Core **não** implementa `RuntimeResolver`.

# Runtime Intent

Modelo neutro (sem runner concreto):

```php
final readonly class RuntimeIntent
{
    public function __construct(
        public string $mode,
        public array $capabilities = [],
    ) {}
}
```

# Resolved Runtime

Intenção separada de estado resolvido (preenchido pelo Forge, armazenado pelo Core):

```php
final readonly class ResolvedRuntime
{
    public function __construct(
        public string $mode,
        public string $engine,
        public string $execution,
        public ?string $supervisor = null,
    ) {}
}
```

**Future consideration:** hoje `engine: mithril` é um valor válido produzido pelo Forge, mas o Core **não** assume que o engine será sempre Mithril. `engine` permanece `string` neutra para permitir outros engines no futuro.

# ProjectOptions

Contrato genérico sem defaults concretos de server/runner:

```php
final readonly class ProjectOptions
{
    public function __construct(
        public string $name,
        public string $preset,
        public string $targetDirectory,
        public ?RuntimeIntent $runtime = null,
        public bool $http = false,
        public bool $messaging = false,
        public bool $modules = false,
        public array $extra = [],
    ) {}
}
```

# Manifest — exemplos canônicos

## Estado inicial (pós-scaffold / pré-resolução)

```yaml
application:
  name: example
  preset: uninitialized

runtime:
  state: unresolved
```

## HTTP resolvido

```yaml
application:
  name: example
  preset: service

runtime:
  mode: http
  engine: mithril
  execution: mithril-http
  supervisor: eregion

features:
  http: true
  messaging: false
```

## Worker standalone (job sem supervisor)

```yaml
application:
  name: example
  preset: custom

runtime:
  mode: job
  engine: mithril
  execution: mithril-job

features:
  http: false
  messaging: true
```

(`supervisor` omitido — equivalente a `null`.)

## Worker supervisionado

```yaml
application:
  name: example
  preset: custom

runtime:
  mode: job
  engine: mithril
  execution: mithril-job
  supervisor: eregion

features:
  http: false
  messaging: true
```

O Core **não** infere supervisor a partir do nome do preset; apenas serializa/deserializa o bloco `runtime` resolvido.

# Job detection (`isJobMode`)

A detecção de modo job **nunca** usa ID de preset (`worker`, `minimal`, `service`, etc.).

Quando o runtime está **resolvido**, a regra é:

```php
$manifest->runtime !== null && $manifest->runtime->mode === 'job';
```

Quando o runtime está **unresolved** (`runtime` ausente ou `state: unresolved`), a API pública atual pode inferir job apenas via `features` (`messaging === true && http === false`) — **não** via preset. Preferir resolver runtime no Forge antes de depender dessa heurística.

# Legacy manifest

Continua suportado na leitura:

```yaml
runtime:
  engine: mithril
  server: eregion
  mode: http
```

O parser normaliza internamente para `ResolvedRuntime` canônico (`execution`, `supervisor`; `server: none` → supervisor nulo). O writer emite o formato canônico (sem `server:`).

# Responsabilidades

## durin-core owns

```text
ProjectOptions
RuntimeIntent
ResolvedRuntime
DurinManifest
manifest parsing / serialization
legacy manifest normalization
runtime-neutral project model (discovery, paths, scaffold plan/writer)
preset contract surface (Preset, PresetRegistry — not preset catalog)
```

## durin-core does NOT own

```text
runtime resolution
runtime registry
runtime provisioning
Eregion installation / configuration
preset resolution (catalog semantics live in durin-presets)
broker execution
process supervision
```

# Invariantes arquiteturais

- Core **nunca** conhece IDs concretos de preset (`minimal`, `service`, `worker`, …) para decidir runtime.
- Core **nunca** escolhe runtime (engine, execution, supervisor).
- Core **nunca** assume Eregion como default genérico.
- Core **pode** representar `supervisor` no manifest, mas **não** decide qual supervisor usar.
- Core **pode** representar runtime `unresolved`.
- **Resolução** de runtime pertence ao **Forge** (`RuntimeResolver` e integrações).

# Orientações removidas (não usar)

Não documentar nem reintroduzir:

```text
runtimeServer=eregion como default do Core
server: eregion implícito no writer genérico
preset == worker ⇒ job / Eregion
worker preset ⇒ supervisor obrigatório no Core
```

Essas regras viviam no modelo antigo; o Core atual só armazena o que foi resolvido externamente.

# Boundaries (dependências)

Core **must not**:

```text
importar Forge
importar Eregion
executar processos
implementar RuntimeResolver
```

# Tests (implementados)

```text
manifest unresolved
HTTP resolved
job standalone
job supervised
legacy server=eregion (normalization + canonical write)
round-trip determinístico
isJobMode sem preset ID
invalid runtime shapes
```

# Definition of Done

**Atendido:** `durin-core` modela estado de runtime (resolvido ou unresolved) sem decidir implementação; parsing legado, serialização canônica e DTOs neutros estão na API pública v0.2.0+.
