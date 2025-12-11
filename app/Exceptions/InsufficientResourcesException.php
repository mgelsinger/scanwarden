<?php

namespace App\Exceptions;

class InsufficientResourcesException extends \Exception
{
    public function __construct(string $message = 'Insufficient resources', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
