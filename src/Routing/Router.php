<?php

namespace App\Routing;

use App\Routing\Attributes\Route as RouteAttribute;
use InvalidArgumentException;
use ReflectionClass;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Routing\PsrRequestHandler;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function addMiddleware($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function register(array $controllerClasses): void
    {
        foreach ($controllerClasses as $controllerClass) {
            if (!is_string($controllerClass) || !class_exists($controllerClass)) {
                throw new InvalidArgumentException("Controller not found: {$controllerClass}");
            }

            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(RouteAttribute::class);

                foreach ($attributes as $attribute) {
                    /** @var RouteAttribute $route */
                    $route = $attribute->newInstance();

                    foreach ($route->methods as $httpMethod) {
                        $this->add($httpMethod, $route->path, [$controllerClass, $method->getName()]);
                    }
                }
            }
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $psr17 = new Psr17Factory();

        $handler = new PsrRequestHandler($this);


        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $handler;

            if ($middleware instanceof MiddlewareInterface) {
                $handler = new class ($middleware, $next) implements RequestHandlerInterface {
                    private MiddlewareInterface $middleware;
                    private RequestHandlerInterface $next;

                    public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
                    {
                        $this->middleware = $middleware;
                        $this->next = $next;
                    }

                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return $this->middleware->process($request, $this->next);
                    }
                };
            } elseif (is_callable($middleware)) {
                $handler = new class ($middleware, $next) implements RequestHandlerInterface {
                    private $callable;
                    private RequestHandlerInterface $next;

                    public function __construct(callable $callable, RequestHandlerInterface $next)
                    {
                        $this->callable = $callable;
                        $this->next = $next;
                    }

                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        $method = $request->getMethod();
                        $path = $request->getUri()->getPath() ?: '/';

                        $called = false;
                        $response = null;

                        $next = function () use (&$called, $request, &$response) {
                            $called = true;
                            $response = ($this->next)->handle($request);
                            return $response;
                        };


                        ($this->callable)($method, $path, $next);

                        if ($called && $response instanceof ResponseInterface) {
                            return $response;
                        }


                        return ($this->next)->handle($request);
                    }
                };
            }
        }

        return $handler->handle($request);
    }


    public function findRoute(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route;
            }
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = rtrim($path, '/');

        if ($trimmed === '') {
            return '/';
        }

        return $trimmed;
    }
}
