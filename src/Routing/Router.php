<?php

namespace App\Routing;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                [$class, $action] = $route['handler'];

                if (!class_exists($class)) {
                    http_response_code(500);
                    echo "Controller not found: $class";
                    return;
                }

                $controller = new $class();

                if (!method_exists($controller, $action)) {
                    http_response_code(500);
                    echo "Method not found: $action";
                    return;
                }

                $controller->$action();
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}