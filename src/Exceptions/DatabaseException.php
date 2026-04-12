<?php
namespace App\Exceptions;

class DatabaseException extends AppException
{
    public function __construct(string $message = "Database error", array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 500, $context, $previous);
    }
}