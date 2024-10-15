<?php

namespace App\Services\Installation\Packages;

use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;

class DebugBar extends AbstractPackages
{
    use InteractsWithLaravel;

    public function __construct(string $workingDirectory, Command $instance)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
    }

    public function installPackage(bool $isNewInstallation): void
    {
        $this->instance->task('Configuring Debug Bar', function (){
            $composerService = new ComposerService($this->workingDirectory, $this->instance);
            $composerService->installDependency('barryvdh/laravel-debugbar',true);
        }, 'Installing...'.PHP_EOL);

    }
}
