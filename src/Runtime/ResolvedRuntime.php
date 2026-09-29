<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Runtime;

/**
 * Resolved execution runtime — intent made concrete by a resolver (e.g. Forge).
 * Core models this state; it does not choose engine/execution/supervisor.
 */
final readonly class ResolvedRuntime
{
    public function __construct(
        public string $mode,
        public string $engine,
        public string $execution,
        public ?string $supervisor = null,
    ) {}
}
