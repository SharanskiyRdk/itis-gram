<?php
namespace App\Exceptions;

class AppException extends \Exception
{
    protected array $context = [];

    public function __construct(string $message = "", int $code = 500, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}