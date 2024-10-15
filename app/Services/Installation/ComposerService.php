<?php

namespace App\Services\Installation;

use App\Exceptions\Installation\FolderIsNotValidException;
use App\Services\Deps\Traits\ExecuteCommands;
use Illuminate\Console\Command;

class ComposerService
{
    private array $composerDeps = [];

    use ExecuteCommands;

    private bool $hasLoaded = false;
    public function __construct(private readonly string $workingDirectory, private readonly Command $instance)
    {
    }

    private function loadContent(): bool
    {
        if ($this->hasLoaded){
            return true;
        }

        $composerFileLocation = $this->workingDirectory.DIRECTORY_SEPARATOR.'composer.json';
        if (file_exists($composerFileLocation)){
            $content = json_decode(file_get_contents($composerFileLocation));

            if ($content === null){
                return false;
            }

            $this->composerDeps = json_decode(file_get_contents($composerFileLocation), true);

            $this->hasLoaded = true;

            return true;
        }else{
            return false;
        }
    }

    /**
     * @throws FolderIsNotValidException
     */
    public function validateComposer(): bool
    {
        if ($this->loadContent()){
            if (!$this->checkDependencyExists('laravel/framework')){
                return false;
            }
            return true;
        }else{
            throw new FolderIsNotValidException();
        }
    }

    public function checkDependencyExists(string $package): bool
    {
        $this->loadContent();

        $parts = explode(':', $package);

        if (!isset($this->composerDeps['require'][trim($parts[0])])){
            return false;
        }
        return true;
    }

    public function checkDependenciesExists(array $packages):bool
    {
        foreach ($packages as $package){
            if ($this->checkDependencyExists($package)){
                return true;
            }
        }
        return false;
    }

    public function installDependency(string $package, bool $isDev = false): void
    {
        if (!$this->checkDependencyExists($package)){
            $this->instance->task('Install dependency '.$package, function () use ($package, $isDev) {
                if ($isDev){
                    $this->executeSail(sprintf('composer require %s --dev -W', $package));
                }else{
                    $this->executeSail(sprintf('composer require %s -W', $package));
                }

                return true;
            }, 'Installing...');
        }
    }

    public function hasFilamentInstalled(): bool
    {
        return $this->checkDependencyExists('filament/filament');
    }

    public function forceReindex(): void
    {
        $this->hasLoaded = false;
    }
}
