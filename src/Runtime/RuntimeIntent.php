<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Runtime;

/**
 * Neutral runtime intent — mode and capabilities, no concrete runner.
 */
final readonly class RuntimeIntent
{
    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        public string $mode,
        public array $capabilities = [],
    ) {}
}
