<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Tests\Unit\Project;

use EreborCodeForge\Durin\Core\Manifest\DurinManifest;
use EreborCodeForge\Durin\Core\Manifest\DurinManifestException;
use EreborCodeForge\Durin\Core\Manifest\DurinManifestParser;
use EreborCodeForge\Durin\Core\Runtime\ResolvedRuntime;
use PHPUnit\Framework\TestCase;

final class DurinManifestParserTest extends TestCase
{
    public function test_parses_unresolved_when_runtime_absent(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: telemetry
  preset: uninitialized
YAML);

        $this->assertSame('telemetry', $manifest->applicationName);
        $this->assertFalse($manifest->isResolved());
        $this->assertNull($manifest->runtime);
        $this->assertSame(['state' => 'unresolved'], $manifest->toArray()['runtime']);
    }

    public function test_parses_explicit_unresolved_state(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: telemetry
  preset: uninitialized
runtime:
  state: unresolved
YAML);

        $this->assertFalse($manifest->isResolved());
        $this->assertNull($manifest->runtime);
    }

    public function test_parses_http_resolved(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: billing
  preset: service
runtime:
  mode: http
  engine: mithril
  execution: mithril-http
  supervisor: eregion
features:
  http: true
  messaging: false
architecture:
  modules: false
YAML);

        $this->assertTrue($manifest->isResolved());
        $this->assertSame('http', $manifest->runtime?->mode);
        $this->assertSame('mithril', $manifest->runtime?->engine);
        $this->assertSame('mithril-http', $manifest->runtime?->execution);
        $this->assertSame('eregion', $manifest->runtime?->supervisor);
        $this->assertFalse($manifest->isJobMode());
    }

    public function test_parses_job_standalone(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: notifications
  preset: custom
runtime:
  mode: job
  engine: mithril
  execution: mithril-job
features:
  http: false
  messaging: true
architecture:
  modules: false
YAML);

        $this->assertTrue($manifest->isResolved());
        $this->assertSame('job', $manifest->runtime?->mode);
        $this->assertSame('mithril-job', $manifest->runtime?->execution);
        $this->assertNull($manifest->runtime?->supervisor);
        $this->assertTrue($manifest->isJobMode());
        $this->assertArrayNotHasKey('supervisor', $manifest->toArray()['runtime']);
    }

    public function test_parses_job_supervised(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: notifications
  preset: custom
runtime:
  mode: job
  engine: mithril
  execution: mithril-job
  supervisor: eregion
features:
  http: false
  messaging: true
architecture:
  modules: false
YAML);

        $this->assertTrue($manifest->isJobMode());
        $this->assertSame('eregion', $manifest->runtime?->supervisor);
    }

    public function test_normalizes_legacy_server_eregion_to_canonical(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: billing
  preset: service
runtime:
  engine: mithril
  server: eregion
features:
  http: true
  messaging: false
architecture:
  modules: false
YAML);

        $this->assertTrue($manifest->isResolved());
        $this->assertSame('http', $manifest->runtime?->mode);
        $this->assertSame('mithril', $manifest->runtime?->engine);
        $this->assertSame('mithril-http', $manifest->runtime?->execution);
        $this->assertSame('eregion', $manifest->runtime?->supervisor);

        $yaml = $manifest->toYaml();
        $this->assertStringContainsString('execution: mithril-http', $yaml);
        $this->assertStringContainsString('supervisor: eregion', $yaml);
        $this->assertStringNotContainsString('server:', $yaml);
    }

    public function test_normalizes_legacy_server_none_to_null_supervisor(): void
    {
        $manifest = (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: notifications
  preset: custom
runtime:
  engine: mithril
  server: none
  mode: job
features:
  http: false
  messaging: true
architecture:
  modules: false
YAML);

        $this->assertSame('job', $manifest->runtime?->mode);
        $this->assertSame('mithril-job', $manifest->runtime?->execution);
        $this->assertNull($manifest->runtime?->supervisor);
        $this->assertTrue($manifest->isJobMode());
    }

    public function test_round_trip_yaml_is_deterministic(): void
    {
        $original = new DurinManifest(
            applicationName: 'catalog',
            preset: 'minimal',
            features: ['http' => true, 'messaging' => false],
            architecture: ['modules' => false],
            runtime: new ResolvedRuntime(
                mode: 'http',
                engine: 'mithril',
                execution: 'mithril-http',
                supervisor: 'eregion',
            ),
        );

        $parsed = (new DurinManifestParser())->parse($original->toYaml());

        $this->assertSame($original->toArray(), $parsed->toArray());
        $this->assertSame($original->toYaml(), $parsed->toYaml());
    }

    public function test_is_job_mode_without_preset_id(): void
    {
        $resolvedJob = new DurinManifest(
            applicationName: 'jobs',
            preset: 'anything',
            features: ['http' => true, 'messaging' => false],
            architecture: ['modules' => false],
            runtime: new ResolvedRuntime(
                mode: 'job',
                engine: 'mithril',
                execution: 'mithril-job',
            ),
        );
        $this->assertTrue($resolvedJob->isJobMode());

        $unresolvedViaFeatures = new DurinManifest(
            applicationName: 'jobs',
            preset: 'anything',
            features: ['http' => false, 'messaging' => true],
            architecture: ['modules' => false],
            runtime: null,
        );
        $this->assertTrue($unresolvedViaFeatures->isJobMode());

        $presetNamedWorkerButHttp = new DurinManifest(
            applicationName: 'api',
            preset: 'worker',
            features: ['http' => true, 'messaging' => false],
            architecture: ['modules' => false],
            runtime: new ResolvedRuntime(
                mode: 'http',
                engine: 'mithril',
                execution: 'mithril-http',
                supervisor: 'eregion',
            ),
        );
        $this->assertFalse($presetNamedWorkerButHttp->isJobMode());
    }

    public function test_rejects_missing_application_name(): void
    {
        $this->expectException(DurinManifestException::class);

        (new DurinManifestParser())->parse(<<<'YAML'
application:
  preset: minimal
YAML);
    }

    public function test_rejects_invalid_runtime_state(): void
    {
        $this->expectException(DurinManifestException::class);
        $this->expectExceptionMessage('runtime.state must be "unresolved"');

        (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: api
  preset: minimal
runtime:
  state: broken
YAML);
    }

    public function test_rejects_canonical_runtime_missing_execution_fields(): void
    {
        $this->expectException(DurinManifestException::class);

        (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: api
  preset: minimal
runtime:
  mode: http
  engine: mithril
  execution: ""
YAML);
    }

    public function test_rejects_legacy_runtime_without_engine(): void
    {
        $this->expectException(DurinManifestException::class);
        $this->expectExceptionMessage('legacy runtime.engine');

        (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: api
  preset: minimal
runtime:
  server: eregion
YAML);
    }

    public function test_rejects_ambiguous_runtime_shape(): void
    {
        $this->expectException(DurinManifestException::class);
        $this->expectExceptionMessage('runtime must be unresolved, canonical');

        (new DurinManifestParser())->parse(<<<'YAML'
application:
  name: api
  preset: minimal
runtime:
  mode: http
  engine: mithril
YAML);
    }
}
