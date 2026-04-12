<?php

namespace App\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = "Resource not found", array $context = [])
    {
        parent::__construct($message, 404, $context);
    }
}