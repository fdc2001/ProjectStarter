<?php

namespace App\Services\Installation\Packages;

use App\Exceptions\Filament\AlreadyHasMultiTenancyException;
use App\Exceptions\Filament\FailedUploadPanelProviderException;
use App\Exceptions\Filament\PanelProviderNotFoundException;
use App\Exceptions\System\CommandFailedException;
use App\Services\Deps\AbstractPackages;
use App\Services\Deps\Traits\InteractsWithLaravel;
use App\Services\Installation\ComposerService;
use App\Services\Installation\Packages\traits\InvitationFeature;
use App\Services\Installation\Packages\traits\MultiTenantFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

class Filament extends AbstractPackages
{
    use InteractsWithLaravel, InvitationFeature, MultiTenantFeature;

    private const FILAMENT_PANEL_PROVIDER_FOLDER_PATH = '/app/Providers/Filament/';
    private const PANEL_PROVIDER = 'PanelProvider.php';

    private const COMMANDS_PATH = '/app/Console/Commands';
    private const PANEL_STUB_PATH = '/stub/tools/commands/Filament/generatePanelCommand.stub';
    private const CONTROL_FILE_NAME = 'Control';
    private string $panelId;
    private bool $isNewInstallation;

    public function __construct(string $workingDirectory, Command $instance, $silent=false)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
    }

    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws FailedUploadPanelProviderException
     * @throws PanelProviderNotFoundException
     * @throws CommandFailedException
     */
    public function installPackage(bool $isNewInstallation): void
    {
        $this->instance->task('Configuring Filament', function () use ($isNewInstallation) {

            $this->isNewInstallation = $isNewInstallation;

            $composerService = new ComposerService($this->workingDirectory, $this->instance);

            $composerService->installDependency(' filament/filament:"^3.2"');

            $this->choosePanel();
            $this->addProfilePageToPanel();
            $this->generateUserResource();
            if (confirm('Do you want use multi tenant?')) {
                $composerService->installDependency('spatie/laravel-sluggable');
                if (!$this->configureMultiTenant()) {
                    info('Current Panel already has multi tenancy.');
                }
            }
            if (confirm('Do you want install filament plugins?')) {
                $filamentPluginsService = new FilamentPlugins($this->workingDirectory, $this->instance);
                $filamentPluginsService->installPackage($isNewInstallation);

                $plugins = $filamentPluginsService->getPluginsConfiguration();

                $this->addPluginToPanelProvider($plugins);
            }
            $filePath = $this->getPanelFilePath();
            $this->formatFile($filePath);
        }, 'Installing...'.PHP_EOL);
    }

    /**
     * @throws PanelProviderNotFoundException
     */
    private function addProfilePageToPanel(): void
    {
        $this->checkPanelFileExistsAndUpdateContent();
        $this->generateProfileStubs();
    }

    /**
     * @throws PanelProviderNotFoundException
     */
    private function checkPanelFileExistsAndUpdateContent(): void
    {
        $filePath = $this->getPanelFilePath();
        if (!file_exists($filePath)) {
            throw new PanelProviderNotFoundException($this->panelId);
        }

        $content = file_get_contents($filePath);
        if (!Str::contains($content, '\App\Filament\Pages\EditProfile::getUrl()')) {
            $content = str_replace(
                '->login()',
                $this->getReplacementContent(),
                $content
            );
            file_put_contents($filePath, $content);
        }
    }

    private function getReplacementContent(): string
    {
        return '->login()' . PHP_EOL . '     ' . "->userMenuItems([
        'profile' => \Filament\Navigation\MenuItem::make()->url(fn (): string => \App\Filament\Pages\EditProfile::getUrl())->hidden(fn (): bool => \Filament\Facades\Filament::getTenant() === null)])";
    }

    private function generateProfileStubs(): void
    {
        $this->generateStub(
            '/stub/app/Filament/Pages/EditProfile.stub',
            '/app/Filament/Pages/',
            'EditProfile'
        );
        $this->generateStub(
            '/stub/resources/views/filament/pages/edit-profile.stub',
            '/resources/views/filament/pages/',
            'edit-profile.blade'
        );
    }

    private function choosePanel(): void
    {
        $panels = $this->listExistingPanels();

        switch (count($panels)) {
            case 0:
                $this->generatePanelCommand();
                break;
            case 1:
                $this->confirmPanel($panels);
                break;
            default:
                $this->selectPanel($panels);

        }
    }

    private function confirmPanel($panels): void
    {
        $result = confirm(sprintf('Found the following panel: %s. Can I use it, or should I create a new one?', $panels[0]), yes: 'Use it.', no: 'No, create a new panel.');
        if (!$result) {
            $this->generatePanelCommand();
        }
        $this->panelId = $panels[0];
    }

    private function selectPanel($panels): void
    {
        $options = array_merge(['<fg=yellow;options=bold,underscore>New Panel</>'], $panels);
        $panel = strip_tags(select('Please choose a panel from the following list:', $options));

        if ($panel == 'New Panel') {
            $this->generatePanelCommand();
        } else {
            $this->panelId = $panel;
        }
    }

    private function generatePanelCommand(): void
    {
        $panelName = text('Name for filament panel:');

        $commandsDirectory = $this->getAbsolutePath(self::COMMANDS_PATH);
        $this->makeDirectory($commandsDirectory);

        if ($this->checkPanelExists($panelName)) {
            if (!confirm('I see that your panel already exists. Can I proceed with working on this panel?')) {
                $this->instance->info('Job stopped');
            }
        } else {
            $this->panelId = $panelName;

            $this->generateStub(
                self::PANEL_STUB_PATH,
                self::COMMANDS_PATH,
                self::CONTROL_FILE_NAME,
                [
                    'PANEL_NAME' => $panelName
                ]
            );
            $this->runArtisanCommand();
            $this->removeBridge();
            $this->removeCommand();
        }
    }

    private function makeDirectory(string $path): void
    {
        if (!file_exists($this->workingDirectory . $path)) {
            mkdir($this->workingDirectory . $path, recursive: true);
        }
    }

    private function removeCommand(): void
    {
        if (file_exists($this->workingDirectory . '/app/Console/Commands/Control.php')) {
            unlink($this->workingDirectory . '/app/Console/Commands/Control.php');
        }
    }

    private function checkPanelExists($panelName): bool
    {
        if (Str::startsWith($this->workingDirectory, '.')) {
            $workingDirectory = getcwd() . substr($this->workingDirectory, 1);
        } else {
            $workingDirectory = getcwd() . '/' . $this->workingDirectory;
        }
        return file_exists($workingDirectory . '/app/Providers/Filament/' . ucfirst($panelName) . 'PanelProvider.php');
    }

    private function listExistingPanels(): array
    {
        $workingDirectory = $this->getAbsolutePath($this->workingDirectory);
        $panelDirectory = $workingDirectory . self::FILAMENT_PANEL_PROVIDER_FOLDER_PATH;

        if (!is_dir($panelDirectory)) {
            return [];
        }

        $panels = [];
        foreach (glob($panelDirectory . '*.php') as $panel) {
            $panelName = str_replace(self::PANEL_PROVIDER, '', basename($panel));
            $panels[] = $panelName;
        }

        return $panels;
    }

    private function getPanelFilePath(): string
    {
        return $this->workingDirectory . self::FILAMENT_PANEL_PROVIDER_FOLDER_PATH . $this->panelId . 'PanelProvider.php';
    }

    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws PanelProviderNotFoundException
     */
    private function checkPanelContainsMultiTenant(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new PanelProviderNotFoundException($this->panelId);
        }

        $content = file_get_contents($filePath);

        if (Str::contains($content, 'tenant(\App\Models')) {
            throw new AlreadyHasMultiTenancyException();
        }
    }

    private function addPluginToPanelProvider(string $configurations): void
    {
        $filePath = $this->getPanelFilePath();
        $content = file_get_contents($filePath);


        $contentToAdd = '->plugins(' . $configurations . ')' . PHP_EOL;


        $pattern = '/->plugins\(\[\s*(?:[^][]+|\[[^][]*])*?\s*\]\)/s';
        $content = preg_replace($pattern, '', $content);

        $updatedContent = str_replace('->login()', '->login()' . PHP_EOL . '        ' . $contentToAdd, $content);

        file_put_contents($filePath, $updatedContent);
    }

    private function generateUserResource(): void
    {
        /*if (file_exists($this->workingDirectory.'/app/Filament/Resources/UserResource.php')){
            return;
        }*/

        $this->executeSail('artisan make:filament-resource User --generate');

        $this->generateStub(
            '/stub/app/Filament/Resources/UserResource.stub',
            '/app/Filament/Resources/',
            'UserResource'
        );
    }

    public function hasMultiTenancy(): bool
    {
        $panels = $this->listExistingPanels();

        if (count($panels) == 0){
            return false;
        }

        foreach ($panels as $panel){
            $this->panelId = $panel;
            try {
                $this->checkPanelContainsMultiTenant($this->getPanelFilePath());
            }catch (AlreadyHasMultiTenancyException $exception){
                return true;
            } catch (PanelProviderNotFoundException $e) {
                return false;
            }
        }
        return false;
    }
}
