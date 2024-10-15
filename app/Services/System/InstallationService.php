<?php

namespace App\Services\System;

use App\Services\Installation\LaravelService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallationService
{
    protected string $workDirectory = '';

    private array $services = [];

    protected bool $isNewInstallation = false;

    public function __construct(private readonly Command $instance)
    {
    }

    public function setParamDirectory($dir): void
    {
        $this->workDirectory = $dir;
    }

    public function setDirectory(): void
    {
        $choice = true;
        $path = getcwd();
        while ($choice) {
            $directories = $this->getPathItems($path);
            if (count($directories) === 3) {
                if ($path !== getcwd())
                    if (confirm('Do you want use this directory?', default: true)) {
                        $this->workDirectory = $path;
                        return;
                    }
            }

            if (LaravelService::checkDirectory($path, $this->instance)) {
                if (confirm('This directory contains a Laravel installation. Do you want to proceed?')) {
                    $choice = false;
                    $this->workDirectory = $path;
                    return;
                }
            }

            $selection = $this->choicePath($directories, $path);
            $selection = strip_tags($selection);


            switch ($selection) {
                case "..":
                {
                    $pathParts = explode(DIRECTORY_SEPARATOR, $path);
                    unset($pathParts[count($pathParts) - 1]);
                    $path = join(DIRECTORY_SEPARATOR, $pathParts);
                    break;
                }
                case "Select":
                {
                    $choice = false;
                    $this->workDirectory = $path;
                    break;
                }
                case "New Directory":
                {
                    $directoryName = text('Name of directory:');
                    $directoryName = str_replace(' ', '-', $directoryName);
                    $path .= DIRECTORY_SEPARATOR . $directoryName;
                    if (!is_dir($path)) {
                        mkdir($path);
                    }
                    $this->workDirectory = $path;
                    break;
                }
                default:
                {
                    $path .= DIRECTORY_SEPARATOR . $selection;
                    break;
                }
            }
        }


    }

    private function getPathItems($path): array
    {
        $dirs = array_filter(glob($path . '/*'), 'is_dir');
        $directories = [];

        foreach ($dirs as $dir) {
            $directories[] = basename($dir);
        }

        $options = [];
        if ($path !== getcwd()) {
            $options[] = '<fg=yellow;options=bold,underscore>Select</>';
        }
        $options[] = '<fg=yellow;options=bold,underscore>New Directory</>';
        $options[] = '..';

        $directories = array_merge($options, $directories);

        return $directories;
    }

    private function choicePath($paths, $currentPath): array|string
    {
        $this->instance->clear();

        $this->instance->info('<fg=white>Current directory:</> <fg=gray>' . $currentPath . '</>');
        return select('Select directory', $paths);
    }

    public function setServices(): void
    {
        $services = ['mysql', 'redis', 'mailpit'];
        $servicesAvailable = [
            'mysql', 'pgsql', 'mariadb', 'redis', 'memcached', 'meilisearch', 'typesense', 'minio', 'selenium', 'mailpit'
        ];

        if (confirm('Would you like to select the services to include in your application?', default: false)) {
            $services = multiselect('Services: ', $servicesAvailable);
        }

        $this->services = $services;
    }

    public function generateProject(): void
    {
        $services = join(',', $this->services);

        $folder = basename($this->workDirectory);
        $dir = str_replace($folder, '', $this->workDirectory);
        if (file_exists($this->workDirectory))
            $this->instance->task('Cleaning directory', function () {
                $this->deleteDirectory($this->workDirectory);
            });
        $this->instance->info('<fg=blue>Okay, please hold on for a few minutes while I complete the setup. Note that I will need your user password to finalize the process.</>');

        $this->instance->task('Installing Laravel', function () use ($services, $folder, $dir) {
            $logs = shell_exec(sprintf('cd ' . $dir . ' && curl -s "https://laravel.build/%s?with=%s" | bash 2>&1', str_replace(' ', '-', $folder), $services));

            $this->isNewInstallation = true;

            return true;
        }, 'Installing...' . PHP_EOL);


        return;
    }

    public function getWorkingDir(): string
    {
        return $this->workDirectory;
    }

    public function checkDirectoryIsLaravelInstallation(): bool
    {
        $laravelService = new LaravelService($this->workDirectory, $this->instance);
        try {
            return $laravelService->checkDirIsValid();
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function isNewInstallation(): bool
    {
        return $this->isNewInstallation;
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }
}
