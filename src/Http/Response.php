<?php

namespace App\Http;

use Psr\Http\Message\ResponseInterface;

final class Response
{
    private static ?ResponseInterface $instance = null;

    public static function set(ResponseInterface $response): void
    {
        self::$instance = $response;
    }

    public static function get(): ?ResponseInterface
    {
        return self::$instance;
    }
}
