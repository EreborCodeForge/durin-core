<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Scaffold;

final readonly class ScaffoldConflict
{
    public function __construct(
        public string $relativePath,
        public string $reason,
    ) {}
}
