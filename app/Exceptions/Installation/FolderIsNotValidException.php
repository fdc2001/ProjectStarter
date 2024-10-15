<?php

namespace App\Exceptions\Installation;

use Exception;

class FolderIsNotValidException extends Exception
{
    public function __construct(string $message = "Installation folder isn´t valid.", int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
