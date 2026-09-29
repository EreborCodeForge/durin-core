<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Manifest;

use EreborCodeForge\Durin\Core\Runtime\ResolvedRuntime;

/**
 * Minimal durin.yaml model — runtime-neutral (resolved or unresolved).
 */
final readonly class DurinManifest
{
    /**
     * @param array{http: bool, messaging: bool} $features
     * @param array{modules: bool} $architecture
     */
    public function __construct(
        public string $applicationName,
        public string $preset,
        public array $features,
        public array $architecture,
        public ?ResolvedRuntime $runtime = null,
    ) {}

    public function isResolved(): bool
    {
        return $this->runtime !== null;
    }

    public function isJobMode(): bool
    {
        if ($this->runtime !== null) {
            return $this->runtime->mode === 'job';
        }

        return $this->features['messaging'] === true && $this->features['http'] === false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $application = $data['application'] ?? null;
        if (!is_array($application)) {
            throw new DurinManifestException('durin.yaml requires application section.');
        }

        $name = $application['name'] ?? null;
        $preset = $application['preset'] ?? null;
        if (!is_string($name) || $name === '' || !is_string($preset) || $preset === '') {
            throw new DurinManifestException('application.name and application.preset are required strings.');
        }

        $runtime = $data['runtime'] ?? null;
        if ($runtime !== null && !is_array($runtime)) {
            throw new DurinManifestException('runtime must be a mapping.');
        }

        $features = $data['features'] ?? ['http' => false, 'messaging' => false];
        if (!is_array($features)) {
            throw new DurinManifestException('features must be a mapping.');
        }

        $architecture = $data['architecture'] ?? ['modules' => false];
        if (!is_array($architecture)) {
            throw new DurinManifestException('architecture must be a mapping.');
        }

        return new self(
            applicationName: $name,
            preset: $preset,
            features: [
                'http' => (bool) ($features['http'] ?? false),
                'messaging' => (bool) ($features['messaging'] ?? false),
            ],
            architecture: [
                'modules' => (bool) ($architecture['modules'] ?? false),
            ],
            runtime: self::parseRuntime(is_array($runtime) ? $runtime : []),
        );
    }

    /**
     * @param array<string, mixed> $runtime
     */
    private static function parseRuntime(array $runtime): ?ResolvedRuntime
    {
        if ($runtime === []) {
            return null;
        }

        if (array_key_exists('state', $runtime)) {
            $state = $runtime['state'];
            if ($state === 'unresolved') {
                return null;
            }

            throw new DurinManifestException('runtime.state must be "unresolved" when present.');
        }

        if (array_key_exists('execution', $runtime)) {
            return self::parseCanonicalRuntime($runtime);
        }

        if (array_key_exists('server', $runtime)) {
            return self::parseLegacyRuntime($runtime);
        }

        throw new DurinManifestException(
            'runtime must be unresolved, canonical (with execution), or legacy (with server).',
        );
    }

    /**
     * @param array<string, mixed> $runtime
     */
    private static function parseCanonicalRuntime(array $runtime): ResolvedRuntime
    {
        $mode = $runtime['mode'] ?? null;
        $engine = $runtime['engine'] ?? null;
        $execution = $runtime['execution'] ?? null;
        $supervisor = $runtime['supervisor'] ?? null;

        if (!is_string($mode) || $mode === '') {
            throw new DurinManifestException('runtime.mode must be a non-empty string.');
        }
        if (!is_string($engine) || $engine === '') {
            throw new DurinManifestException('runtime.engine must be a non-empty string.');
        }
        if (!is_string($execution) || $execution === '') {
            throw new DurinManifestException('runtime.execution must be a non-empty string.');
        }
        if ($supervisor !== null && (!is_string($supervisor) || $supervisor === '')) {
            throw new DurinManifestException('runtime.supervisor must be a non-empty string when present.');
        }

        return new ResolvedRuntime(
            mode: $mode,
            engine: $engine,
            execution: $execution,
            supervisor: $supervisor,
        );
    }

    /**
     * @param array<string, mixed> $runtime
     */
    private static function parseLegacyRuntime(array $runtime): ResolvedRuntime
    {
        $engine = $runtime['engine'] ?? null;
        $server = $runtime['server'] ?? null;
        $mode = $runtime['mode'] ?? 'http';

        if (!is_string($engine) || $engine === '') {
            throw new DurinManifestException('legacy runtime.engine must be a non-empty string.');
        }
        if (!is_string($server) || $server === '') {
            throw new DurinManifestException('legacy runtime.server must be a non-empty string.');
        }
        if (!is_string($mode) || $mode === '') {
            throw new DurinManifestException('runtime.mode must be a non-empty string when present.');
        }

        $supervisor = $server === 'none' ? null : $server;

        return new ResolvedRuntime(
            mode: $mode,
            engine: $engine,
            execution: $engine . '-' . $mode,
            supervisor: $supervisor,
        );
    }

    /**
     * @return array{
     *   application: array{name: string, preset: string},
     *   runtime: array{state: string}|array{mode: string, engine: string, execution: string, supervisor?: string},
     *   features: array{http: bool, messaging: bool},
     *   architecture: array{modules: bool}
     * }
     */
    public function toArray(): array
    {
        return [
            'application' => [
                'name' => $this->applicationName,
                'preset' => $this->preset,
            ],
            'runtime' => $this->runtimeToArray(),
            'features' => $this->features,
            'architecture' => $this->architecture,
        ];
    }

    /**
     * @return array{state: string}|array{mode: string, engine: string, execution: string, supervisor?: string}
     */
    private function runtimeToArray(): array
    {
        if ($this->runtime === null) {
            return ['state' => 'unresolved'];
        }

        $data = [
            'mode' => $this->runtime->mode,
            'engine' => $this->runtime->engine,
            'execution' => $this->runtime->execution,
        ];

        if ($this->runtime->supervisor !== null) {
            $data['supervisor'] = $this->runtime->supervisor;
        }

        return $data;
    }

    public function toYaml(): string
    {
        return DurinManifestParser::dump($this->toArray());
    }
}
