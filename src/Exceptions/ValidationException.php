<?php

namespace App\Exceptions;

class ValidationException extends AppException
{
    private array $errors = [];

    public function __construct(string $message = "Validation error", array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}