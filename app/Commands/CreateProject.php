<?php

namespace App\Commands;

use App\Exceptions\Filament\AlreadyHasMultiTenancyException;
use App\Exceptions\Filament\FailedUploadPanelProviderException;
use App\Exceptions\Filament\PanelProviderNotFoundException;
use App\Exceptions\System\CommandFailedException;
use App\Services\Installation\LaravelService;
use App\Services\System\InspiringService;
use App\Services\System\InstallationService;
use App\Services\System\SystemReqs;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\confirm;


class CreateProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-project {--dir=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start your awesome project 🔥';

    /**
     * Execute the console command.
     */
    private const CREATION_MSG = 'Creating project...';
    private const STOP_MSG = 'Ok, I stopped my work.';

    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws FailedUploadPanelProviderException
     * @throws PanelProviderNotFoundException
     * @throws CommandFailedException
     */
    public function handle(): void
    {
        $installationService = $this->initializeInstallationService();

        if (!$this->checkRequirements()) {
            return;
        }
        if (!$this->checkOperatingSystem()) {
            return;
        }

        $this->greetUser();

        $this->handleDirectoryOption($installationService);

        if (!$installationService->checkDirectoryIsLaravelInstallation()) {
            if (!confirm('<fg=red>Warning: All existing content in the selected folder will be permanently deleted. Are you sure you want to proceed?</>')) {
                $this->line(self::STOP_MSG);
                return;
            }
            $this->info(self::CREATION_MSG);
            $installationService->setServices();
            $installationService->generateProject();
        }

        $laravelService = $this->initializeLaravelService($installationService->getWorkingDir());


        $laravelService->startUpProject($installationService->isNewInstallation());

        if ((new SystemReqs())->checkGit()){
            if (!file_exists($installationService->getWorkingDir().'/.git')){
                $this->info('Creating GIT repository.');
                $laravelService->execute(['git'], 'init --initial-branch=main');
                $laravelService->execute(['git'], 'add .');
                $laravelService->execute(['git'], 'commit -m "Start up project"');
            }
        }
        $this->info(InspiringService::getMessage());
    }

    private function initializeInstallationService(): InstallationService
    {
        return new InstallationService($this);
    }

    private function initializeLaravelService(string $workDir): LaravelService
    {
        return new LaravelService($workDir, $this);
    }

    private function checkOperatingSystem(): bool
    {
        if (windows_os()) {
            $this->error("Error: This script is not compatible with Windows.");
            $this->error("To use this script, please configure the Windows Subsystem for Linux (WSL).");
            $this->error("For detailed instructions on setting up WSL, refer to the [official WSL documentation](https://docs.microsoft.com/en-us/windows/wsl/install).");
            return false;
        }

        return true;
    }

    private function greetUser(): void
    {
        $this->info('<fg=blue>"Hello! I´m here to assist you in creating an amazing and beautiful project. Let´s make something great together! </>');
    }

    private function handleDirectoryOption(InstallationService $installationService): void
    {
        if ($this->option('dir') === null) {
            $installationService->setDirectory();
        } else {
            $installationService->setParamDirectory($this->option('dir'));
        }
    }

    private function checkRequirements(): bool
    {
        $checkService = new SystemReqs();
        $this->info('<fg=white>Checking requirements...</>');
        if (!$checkService->checkDocker()){
            $this->clear();
            $this->error('Hey! You don´t have docker installed, please install to continue.');
            return false;
        }
        return true;
    }

    public function clear(): void
    {
        $lines = shell_exec('tput lines');

        printf("\033[%dS", $lines);
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
