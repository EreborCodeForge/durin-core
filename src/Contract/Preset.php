<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Contract;

use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

interface Preset
{
    public function name(): string;

    public function scaffold(ProjectOptions $options): ScaffoldPlan;
}
