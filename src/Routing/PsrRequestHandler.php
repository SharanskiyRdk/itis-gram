<?php

namespace App\Routing;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Core\ErrorHandler;

class PsrRequestHandler implements RequestHandlerInterface
{
    private Router $router;
    private Psr17Factory $psr17;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->psr17 = new Psr17Factory();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath() ?: '/';

        $route = $this->router->findRoute($method, $path);

        if ($route === null) {
            throw new \RuntimeException('Not Found', 404);
        }

        [$class, $action] = $route['handler'];

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller not found: {$class}", 500);
        }

        $controller = new $class();

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Method not found: {$action}", 500);
        }

        ob_start();
        $controller->$action();
        $content = (string) ob_get_clean();

        $body = $this->psr17->createStream($content);
        return $this->psr17->createResponse(200)->withBody($body);
    }
}
