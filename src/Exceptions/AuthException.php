<?php

namespace App\Exceptions;

class AuthException extends AppException
{
    public function __construct(string $message = "Authentication failed", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}