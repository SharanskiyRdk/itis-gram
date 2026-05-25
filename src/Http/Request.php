<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;

final class Request
{
    private static ?ServerRequestInterface $instance = null;

    public static function set(ServerRequestInterface $request): void
    {
        self::$instance = $request;
    }

    public static function get(): ?ServerRequestInterface
    {
        return self::$instance;
    }
}
