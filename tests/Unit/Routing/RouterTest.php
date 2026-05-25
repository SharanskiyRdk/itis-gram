<?php

namespace Tests\Unit\Routing;

use App\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/DummyController.php';

use App\Controllers\AbstractController;
use App\Routing\Attributes\Route;

class RouterTest extends TestCase
{
    public function testFindRouteNormalizesPath(): void
    {
        $router = new Router();
        $router->add('GET', '/login', [DummyController::class, 'show']);

        $route = $router->findRoute('GET', '/login/');

        self::assertNotNull($route);
        self::assertSame('/login', $route['path']);
    }

    public function testRegisterAndMiddlewareHandle(): void
    {
        $router = new Router();
        $router->register([RoutedController::class]);

        self::assertNotNull($router->findRoute('GET', '/hello'));
        self::assertNotNull($router->findRoute('POST', '/multi'));

        $called = false;
        $router->addMiddleware(function (string $method, string $path, callable $next) use (&$called): ResponseInterface {
            $called = true;

            return $next();
        });

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', 'https://example.test/hello');
        $response = $router->handle($request);

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('hello', (string)$response->getBody());
    }

    public function testHandleReturnsResponseFromController(): void
    {
        $router = new Router();
        $router->add('GET', '/dummy', [DummyController::class, 'show']);

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', 'https://example.test/dummy');
        $response = $router->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('dummy-ok', (string) $response->getBody());
    }
}
