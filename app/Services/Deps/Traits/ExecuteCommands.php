<?php

namespace App\Services\Deps\Traits;

use App\Exceptions\System\CommandFailedException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

trait ExecuteCommands
{
    protected function checkFileExists(array $filePath, $checkIsExecutable = false): bool
    {
        $file = $this->formatPath($filePath);

        if (file_exists($file)){
            if ($checkIsExecutable){
                return is_executable($file);
            }else{
                return true;
            }
        }
        return false;
    }

    protected function formatPath(array $filePath, $withWorkingDir = true): string
    {
        $path = join(DIRECTORY_SEPARATOR, $filePath);
        if ($withWorkingDir)
            return $this->workingDirectory.DIRECTORY_SEPARATOR.$path;
        else {
            return $path;
        }

    }

    /**
     * @throws CommandFailedException
     */
    public function execute(array $filePath, $command = ''): string
    {
        $file = $this->formatPath($filePath, false);
        exec(sprintf('cd %s && %s %s 2>&1;', $this->workingDirectory, $file, $command), $output, $exitCode);

        if ($exitCode == 0){
            return  join(PHP_EOL,$output);
        }else{
            throw new CommandFailedException($output, $file.' '.$command);
        }
    }

    protected function executeSail($command = ''): false|string|null
    {
        return $this->execute(['vendor', 'bin', 'sail'], $command);
    }
}
