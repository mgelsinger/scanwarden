<?php

namespace App\Exceptions;

class CannotAffordTransmutationException extends \Exception
{
    public function __construct(string $message = 'Cannot afford this transmutation', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
