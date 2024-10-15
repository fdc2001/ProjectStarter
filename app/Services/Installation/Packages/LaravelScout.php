<?php

namespace App\Services\Installation\Packages;

use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use function Laravel\Prompts\select;

class LaravelScout extends AbstractPackages
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
        $this->instance->task('Configuring Laravel Scout', function () {
            $composerService = $this->composerService;
            $composerService->forceReindex();
            $composerService->installDependency('laravel/scout');

            if ($composerService->hasFilamentInstalled()) {
                $composerService->installDependency('kainiklas/filament-scout');
            }

            $this->executeSail('artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"');
            info('Laravel Scout dependency has been installed. To implement it with Filament, please refer to the Filament documentation: <options=bold>Filament Plugins: Kainiklas Laravel Scout</>: https://filamentphp.com/plugins/kainiklas-laravel-scout');

            $this->chooseSearchEngine();
        }, 'Installing...'.PHP_EOL);
    }

    private function chooseSearchEngine(): void
    {
        $options = [
            'algolia/algoliasearch-client-php'=>'algolia',
            'meilisearch/meilisearch-php http-interop/http-factory-guzzle' => 'Meilisearch',
            'typesense/typesense-php' => 'Typesense'
            ];

        $check = [];

        foreach ($options as $package => $option){
            $check[] = $package;
        }

        if (!$this->composerService->checkDependenciesExists($check)){
            $default = 'meilisearch/meilisearch-php http-interop/http-factory-guzzle';

            $searchEngine = select('Choose Search Engine for your application', $options, $default, required: true);

            $this->composerService->installDependency($searchEngine);
        }

        $this->executeSail('artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"');

        info('Laravel Scout has been installed, to finish configuration please check documentation: https://laravel.com/docs/11.x/scout');
    }
}
