<?php

namespace App\Services\Installation\Packages;

use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;

class SpatieMediaLibrary extends AbstractPackages
{
    use InteractsWithLaravel;

    public function __construct(string $workingDirectory, Command $instance)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
    }

    private const ARTISAN_PUBLISH_COMMAND_ONE = 'artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"';
    private const ARTISAN_PUBLISH_COMMAND_TWO = 'artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"';

    public function installPackage(bool $isNewInstallation): void
    {
        $this->instance->task('Configuring Media Library', function () {
            $composerService = new ComposerService($this->workingDirectory, $this->instance);
            $composerService->installDependency('spatie/laravel-medialibrary');
            if ($composerService->hasFilamentInstalled()) {
                $composerService->installDependency('filament/spatie-laravel-media-library-plugin:"^3.2"');
            }
            $this->executeSail(self::ARTISAN_PUBLISH_COMMAND_ONE);
            $this->migrate();
            $this->executeSail(self::ARTISAN_PUBLISH_COMMAND_TWO);
        }, 'Installing...'.PHP_EOL);
    }
}
