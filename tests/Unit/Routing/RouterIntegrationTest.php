<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use App\Routing\Router;
use App\Routing\Attributes\Route as RouteAttribute;
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;

class FakeController
{
    #[RouteAttribute('/x', 'GET')]
    public function a(): void
    {
        echo 'ok';
    }

    #[RouteAttribute('/y', ['GET', 'POST'])]
    public function b(): void
    {
        echo 'multi';
    }
}

class RouterIntegrationTest extends TestCase
{
    public function testRegisterAndFindAndHandle(): void
    {
        $router = new Router();
        $router->register([FakeController::class]);

        $route = $router->findRoute('GET', '/x');
        self::assertNotNull($route);
        self::assertSame('/x', $route['path']);

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', 'https://example.test/x');
        $resp = $router->handle($request);
        self::assertSame(200, $resp->getStatusCode());
        self::assertSame('ok', (string)$resp->getBody());
    }
}
