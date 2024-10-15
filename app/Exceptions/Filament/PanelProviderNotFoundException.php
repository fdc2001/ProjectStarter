<?php

namespace App\Exceptions\Filament;

use Exception;

class PanelProviderNotFoundException extends Exception
{
    public function __construct(string $file,$message = "Panel provider %s was not found.", $code = 400)
    {
        $message = sprintf($message, $file);
        parent::__construct($message, $code);
    }
}
