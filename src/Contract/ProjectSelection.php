<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Contract;

/**
 * Immutable project selection carried from the CLI into scaffold and manifest planning.
 * Scaling details stay here so RuntimePlan continues to model only execution and supervision.
 */
final readonly class ProjectSelection
{
    /**
     * @param list<string> $features capability ids, already normalized
     */
    public function __construct(
        public string $presetId,
        public array $features = [],
        public string $supervisorChoice = 'auto',
        public ?string $scalingStrategy = null,
        public ?int $workersMin = null,
        public ?int $workersMax = null,
        public ?string $resourceClass = null,
        public ?int $memoryMb = null,
    ) {}
}
