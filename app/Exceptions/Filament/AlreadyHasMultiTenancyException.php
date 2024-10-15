<?php

namespace App\Exceptions\Filament;

use Exception;

class AlreadyHasMultiTenancyException extends Exception
{
    public function __construct($message = "This panel already has multi tenancy configured.", $code = 400)
    {
        parent::__construct($message, $code);
    }
}
