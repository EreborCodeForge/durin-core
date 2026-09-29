<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Contract;

use EreborCodeForge\Durin\Core\Runtime\RuntimeIntent;

/**
 * Options passed to a preset when building a ScaffoldPlan.
 * Runtime intent is optional and neutral — no concrete engine/server defaults.
 */
final readonly class ProjectOptions
{
    /**
     * @param array<string, mixed> $extra
     */
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
