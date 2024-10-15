<?php

namespace App\Exceptions\Installation;

use Exception;

class ArtisanNotExistsException extends Exception
{
    public function __construct($message = "Artisan file not exists.", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
