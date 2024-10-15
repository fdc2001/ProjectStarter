<?php

namespace App\Services\Deps\Traits;



use App\Exceptions\System\CommandFailedException;
use Binafy\LaravelStub\LaravelStub;
use Carbon\Carbon;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;

trait InteractsWithLaravel
{
    private const MIGRATIONS_PATH = '/database/migrations/';

    protected function generateStub($from, $to, $name, $replaces = []): void
    {
        $this->generateDirectory($this->workingDirectory . $to);
        try{
            (new LaravelStub())->from(__DIR__.'/../../../..'.$from)
                ->to($this->workingDirectory . $to)
                ->name($name)
                ->ext('php')
                ->replaces($replaces)
                ->generate();
        }catch (\Exception){
            dd(__DIR__.'/../../../..'.$from);
        }

    }

    private function generateDirectory($dir): true
    {
        if (file_exists($dir)){
            return true;
        }
        mkdir($dir, recursive: true);
        return true;
    }

    private function generateFile(): void
    {
        $this->generateStub(
            '/stub/tools/bridge/bridge.stub',
            '',
            '/bridge',
        );
    }

    protected function runArtisanCommand():false|null|string
    {
        $this->generateFile();

        return $this->executeSail('php bridge.php');
    }

    protected function removeBridge(): void
    {
        if (file_exists($this->workingDirectory.DIRECTORY_SEPARATOR.'bridge.php'))
            unlink($this->workingDirectory.DIRECTORY_SEPARATOR.'bridge.php');
    }

    protected function migrate($withSeed = false, $fresh = false): void
    {
        $this->instance->task('Running migrations', function () use ($fresh) {
            if ($fresh) {
                $this->executeSail('php artisan migrate:fresh');
            } else {
                $this->executeSail('php artisan migrate');
            }
            return true;
        }, 'migrating...');
        if ($withSeed){
            if (confirm('Would you like to run the database seeds now?')) {
                $this->instance->task('Running seeds', function () {
                    $this->executeSail('php artisan DB:seed');
                    return true;
                });
            }
        }
    }

    protected function generateMigrations(array $tables): void
    {
        $today = Carbon::today()->format('Y_m_d');
        $count = $this->countFilesStartWith($this->workingDirectory.self::MIGRATIONS_PATH, $today) + 1;
        foreach ($tables as $table => $replaces) {
            $count++;
            $this->generateStub(
                "/stub" . self::MIGRATIONS_PATH . "0000_00_00_000000_{$table}.stub",
                self::MIGRATIONS_PATH,
                $today.'_'. Str::padLeft($count+1, 6, 0) . "_{$table}",
                $replaces
            );
        }
    }

    protected function generateModel($model, $replacement = []): void
    {
        $this->generateStub(
            '/stub/app/Models/'.$model.'.stub',
            '/app/Models',
            $model,
            $replacement
        );
    }

    /**
     * @throws CommandFailedException
     */
    protected function formatFile(string $filePath): void
    {
        $filePath = str_replace($this->workingDirectory.'/', '', $filePath);
        $this->executeSail('pint '.$filePath);
    }

    private function countFilesInDirectory($directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        // Scan the directory
        $files = scandir($directory);

        // Filter out the current (.) and parent (..) directory entries
        $files = array_diff($files, array('.', '..'));

        // Filter out only files (exclude directories)
        $files = array_filter($files, function ($file) use ($directory) {
            return is_file($directory . DIRECTORY_SEPARATOR . $file);
        });

        // Return the count of files
        return count($files);
    }

    private function countFilesStartWith($directory, $today): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $files = glob($directory . $today . '*.php');

        return count($files);
    }
}
