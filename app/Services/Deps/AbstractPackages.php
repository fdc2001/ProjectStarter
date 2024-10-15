<?php

namespace App\Services\Deps;

use App\Services\Deps\Interface\PackageInterface;
use App\Services\Deps\Traits\ExecuteCommands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

abstract class AbstractPackages implements PackageInterface
{
    use ExecuteCommands;
    protected string $workingDirectory;
    protected Command $instance;

    protected function getAbsolutePath($directory): string
    {
        if (Str::startsWith($directory, '.')) {
            return getcwd() . substr($directory, 1);
        } else {
            return $directory;
        }
    }
}
