<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Scaffold;

enum ScaffoldActionType: string
{
    case CreateDirectory = 'create_directory';
    case WriteFile = 'write_file';
}
