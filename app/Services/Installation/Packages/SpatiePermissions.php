<?php

namespace App\Services\Installation\Packages;

use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;

class SpatiePermissions extends AbstractPackages
{
    use InteractsWithLaravel;

    private ComposerService $composerService;

    public function __construct(string $workingDirectory, Command $instance)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
        $this->composerService = new ComposerService($this->workingDirectory, $this->instance);

    }


    public function installPackage(bool $isNewInstallation): void
    {
        $this->instance->task('Configuring Permissions', function () {

            $this->composerService->installDependency('spatie/laravel-permission');
            if ($this->composerService->hasFilamentInstalled()) {
                $this->composerService->installDependency('filament/spatie-laravel-media-library-plugin:"^3.2"');
            }
            $this->executeSail('artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"');
            #$this->migrate();

            if ($this->composerService->hasFilamentInstalled()) {
                $this->checkMultiTenancy();
            }
        },'Installing...'.PHP_EOL);
    }

    public function checkMultiTenancy(): void
    {
        $filamentService = new Filament($this->workingDirectory, $this->instance);

        $configFilePath = $this->workingDirectory.'/config/permission.php';
        if (!$filamentService->hasMultiTenancy()){
            return;
        }

        $config = require_once $configFilePath;

        $content = file_get_contents($configFilePath);
        $contentFind = "'teams' => ".($config['teams']?'true':'false').",";

        $content = str_replace($contentFind, "'teams' => true,", $content);

        file_put_contents($configFilePath, $content);
    }
}
