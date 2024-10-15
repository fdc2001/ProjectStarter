<?php

namespace App\Exceptions\System;

use Exception;

class CommandFailedException extends Exception
{
    private array $logs;
    private string $command;
    public function __construct(array $logs, string $command, string $message = "Command execution failed: ", int $code = 0)
    {
        $this->logs = $logs;
        $this->command = $command;
        parent::__construct($message.' '.$command.PHP_EOL.'Logs: '.PHP_EOL.implode(PHP_EOL, $this->logs), $code);
    }

    public function context(): array
    {
        return ['logs' => $this->logs, 'command' => $this->command];
    }

    public function render()
    {
        return 'Execution error. Command: '.$this->command.PHP_EOL.'Logs: '.PHP_EOL.implode(PHP_EOL, $this->logs);
    }
}
