<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Core\Output;

final class NullConsoleOutput implements ConsoleOutput
{
    public function line(string $message): void
    {
    }

    public function info(string $message): void
    {
    }

    public function warning(string $message): void
    {
    }

    public function error(string $message): void
    {
    }
}
