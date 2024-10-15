<?php

namespace App\Exceptions\Filament;

use Exception;

class FailedUploadPanelProviderException extends Exception
{
    public function __construct(string $file,string $message = "Failed to update panel provider: %s", int $code = 500)
    {
        $message = sprintf($message, $file);
        parent::__construct($message, $code);
    }
}
