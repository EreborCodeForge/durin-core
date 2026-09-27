<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Contract;

interface PresetRegistry
{
    public function register(Preset $preset): void;

    public function has(string $name): bool;

    public function get(string $name): Preset;

    /**
     * @return list<string>
     */
    public function names(): array;
}
