<?php

namespace App\Services\Installation\Packages;

use App\Exceptions\System\CommandFailedException;
use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class LaravelModules extends AbstractPackages
{
    use InteractsWithLaravel;

    public function __construct(string $workingDirectory, Command $instance)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
    }

    /**
     * @throws CommandFailedException
     */
    public function installPackage(bool $isNewInstallation): void
    {
        $this->instance->task('Configuring Laravel Modules', function () {
            $composerService = new ComposerService($this->workingDirectory, $this->instance);
            $this->execute(['composer'], 'config --no-plugins allow-plugins.wikimedia/composer-merge-plugin true');
            $composerService->installDependency('nwidart/laravel-modules');
            $this->executeSail('artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"');

            if (confirm('Do you want create a new module in your application?')) {
                $name = text('Name for your module: ', required: true);

                $this->executeSail('artisan module:make ' . $name);
            }
        }, 'Installing...'.PHP_EOL);
    }
}
