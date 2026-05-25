<?php

namespace App\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public string $path;
    public array $methods;

    public function __construct(string $path, string|array $methods = 'GET')
    {
        $this->path = $path;
        $this->methods = array_map(
            static fn (string $method): string => strtoupper($method),
            (array) $methods
        );
    }
}
