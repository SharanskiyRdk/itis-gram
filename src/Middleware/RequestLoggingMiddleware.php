<?php

namespace App\Middleware;

use App\Services\LoggerService;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestLoggingMiddleware implements MiddlewareInterface
{
    private LoggerService $logger;

    public function __construct(?LoggerService $logger = null)
    {
        $this->logger = $logger ?? LoggerService::getInstance();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startedAt = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $exception) {
            $this->logger->error('Request failed', [
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
                'status' => 500,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        $statusCode = $response->getStatusCode();

        $this->logger->info('Request handled', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $statusCode,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }
}
