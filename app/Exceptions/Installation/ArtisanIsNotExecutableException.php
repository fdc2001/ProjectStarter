<?php

namespace App\Exceptions\Installation;

use Exception;

class ArtisanIsNotExecutableException extends Exception
{
    public function __construct($message = "Artisan file is not executable.", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
