<?php

namespace App\Services\Installation\Packages;

use App\Services\Deps\AbstractPackages;
use App\Services\Installation\ComposerService;
use Illuminate\Console\Command;
use phpDocumentor\Reflection\Types\Self_;
use function Laravel\Prompts\multiselect;

class FilamentPlugins extends AbstractPackages
{
    private const PLUGINS = [
        'dutchcodingcompany/filament-developer-logins',
        'flowframe/laravel-trend',
        'pxlrbt/filament-environment-indicator',
    ];

    private array $installedPlugins = [];

    public function __construct(string $workingDirectory, Command $instance)
    {
        $this->workingDirectory = $workingDirectory;
        $this->instance = $instance;
        $this->instance->info('<fg=blue>Okay, lets go Install Filament plugins</>');
    }

    public function installPackage(bool $isNewInstallation): void
    {
        $installPlugins = multiselect('Choose same plugins to install', self::PLUGINS);
        $composerService = new ComposerService($this->workingDirectory, $this->instance);

        $this->installedPlugins = $installPlugins;

        foreach ($installPlugins as $plugin){
            $composerService->installDependency($plugin);
        }
    }

    public function getPluginsConfiguration(): string
    {
        $configurations = '[';
        foreach ($this->installedPlugins as $plugin){
            $configurations.= $this->getPluginConfiguration($plugin);
        }
        $configurations.=']';
        return $configurations;
    }

    public function getCommandOption(): string
    {
        return '';
    }

    public function getPluginConfiguration($plugin)
    {
        switch ($plugin){
            case 'dutchcodingcompany/filament-developer-logins':{
                return "
                  \DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin::make()
                  ->enabled()
                  ->switchable(false)
                  ->users([
                      'Admin' => 'admin@example.com',
                  ]),";
                break;
            }
            case 'pxlrbt/filament-environment-indicator':{
                return "
                  \pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin::make()
                  ->color(fn () => match (app()->environment()) {
                      'production' => Color::Green,
                      'staging' => Color::Orange,
                      default => Color::Rose,
                  })
                  ->showBadge(true)
                  ->showBorder(false)
                  ->visible(true),";
                break;
            }
            default: return '';

        }
    }
}
