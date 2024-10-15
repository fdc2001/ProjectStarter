<?php

namespace App\Services\Installation;

use App\Exceptions\Filament\AlreadyHasMultiTenancyException;
use App\Exceptions\Filament\FailedUploadPanelProviderException;
use App\Exceptions\Filament\PanelProviderNotFoundException;
use App\Exceptions\Installation\ArtisanIsNotExecutableException;
use App\Exceptions\Installation\ArtisanNotExistsException;
use App\Exceptions\Installation\FolderIsNotValidException;
use App\Services\Deps\Traits\ExecuteCommands;
use App\Services\Installation\Packages\DebugBar;
use App\Services\Installation\Packages\Filament;
use App\Services\Installation\Packages\LaravelModules;
use App\Services\Installation\Packages\LaravelScout;
use App\Services\Installation\Packages\SpatieMediaLibrary;
use App\Services\Installation\Packages\SpatiePermissions;
use Illuminate\Console\Command;
use function Laravel\Prompts\multiselect;

class LaravelService
{
    use ExecuteCommands;

    public const Packages = [
        Filament::class => 'Admin panel (Filament)',
        SpatieMediaLibrary::class => 'Manage images in your project',
        LaravelScout::class => 'Search engine (Laravel Scout)',
        DebugBar::class => 'Debug bar for laravel',
        SpatiePermissions::class => 'Spatie Permissions',
        LaravelModules::class => 'Modular organization for scalable apps. (Laravel Modules)'
    ];

    public function __construct(private readonly string $workingDirectory, private readonly Command $instance)
    {
    }


    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws FailedUploadPanelProviderException
     * @throws PanelProviderNotFoundException
     */
    public function startUpProject(bool $isNewInstallation): void
    {
        if (!$this->checkDirIsValid()){
            return;
        }

        $this->runServer();

        $packages = multiselect('Chose packages to install:', self::Packages, );

        foreach ($packages as $package){
            $packageService = new $package($this->workingDirectory, $this->instance);
            $packageService->installPackage($isNewInstallation);
        }

    }

    public function runServer(): bool
    {
        if (!$this->checkFileExists(['vendor','bin','sail'])){
            return false;
        }

        $this->executeSail( 'up -d');

        $this->instance->info('Server started 🎉');

        return true;
    }

    public function checkDirIsValid(): bool
    {
        $composerService = new ComposerService($this->workingDirectory, $this->instance);
        try {
            if ($composerService->validateComposer()){
                try {
                    $this->checkArtisanFile();
                }catch (ArtisanIsNotExecutableException | ArtisanNotExistsException){
                    return false;
                }
                return true;
            }
        }catch (FolderIsNotValidException){
            return false;
        }

        return  false;
    }

    public static function checkDirectory($path, Command $instance): bool
    {
        $instance = new self($path, $instance);
        return $instance->checkDirIsValid();
    }


    /**
     * @throws ArtisanNotExistsException
     * @throws ArtisanIsNotExecutableException
     */
    private function checkArtisanFile(): true
    {
        $artisanFile = $this->workingDirectory.DIRECTORY_SEPARATOR.'artisan';

        if (file_exists($artisanFile)){
            if (!is_executable($artisanFile)) {
                throw new ArtisanIsNotExecutableException();
            }
            return true;
        }
        throw new ArtisanNotExistsException();
    }
}
