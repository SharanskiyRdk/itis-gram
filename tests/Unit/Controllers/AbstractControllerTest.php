<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\AbstractController;
use App\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\DatabaseMockTrait;

class TestableController extends AbstractController
{
    public ?array $jsonPayload = null;
    public ?int $jsonStatus = null;
    public ?string $redirectTarget = null;
    public ?array $renderPayload = null;

    public function callRender(string $view, array $data = []): void
    {
        $this->render($view, $data);
    }

    public function callJson(array $data, int $statusCode = 200): void
    {
        $this->json($data, $statusCode);
    }

    public function callRedirect(string $path): void
    {
        $this->redirect($path);
    }

    public function callRedirectBack(string $fallback = '/'): void
    {
        $this->redirectBack($fallback);
    }

    public function callVerifyCsrf(): void
    {
        $this->verifyCsrf();
    }

    public function callRequireAuth(): void
    {
        $this->requireAuth();
    }

    public function callRequireAdmin(): void
    {
        $this->requireAdmin();
    }

    public function exposeCurrentUserId(): ?int
    {
        return $this->currentUserId();
    }

    public function exposeCurrentUserName(): ?string
    {
        return $this->currentUserName();
    }

    public function exposeCurrentUserEmail(): ?string
    {
        return $this->currentUserEmail();
    }

    public function exposeCsrfToken(): string
    {
        return $this->csrfToken();
    }

    public function exposeQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParam($key, $default);
    }

    public function exposeBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->bodyParam($key, $default);
    }

    public function exposeCookieParam(string $key, mixed $default = null): mixed
    {
        return $this->cookieParam($key, $default);
    }

    public function exposeServerParam(string $key, mixed $default = null): mixed
    {
        return $this->serverParam($key, $default);
    }

    public function exposeHeaderParam(string $key, mixed $default = null): mixed
    {
        return $this->headerParam($key, $default);
    }

    public function exposeUploadedFileParam(string $key): ?array
    {
        return $this->uploadedFileParam($key);
    }

    public function exposeIsAjaxRequest(): bool
    {
        return $this->isAjaxRequest();
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        $this->jsonPayload = $data;
        $this->jsonStatus = $statusCode;
        throw new RuntimeException('json called');
    }

    protected function redirect(string $path): void
    {
        $this->redirectTarget = $path;
    }

    protected function render(string $view, array $data = []): void
    {
        $this->renderPayload = ['view' => $view, 'data' => $data];
        parent::render($view, $data);
    }
}

class AbstractControllerTest extends TestCase
{
    use DatabaseMockTrait;

    protected function tearDown(): void
    {
        $this->resetDatabaseMock();
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [];
        $_SESSION = [];
    }

    public function testRequestHelpersAndAuthFlows(): void
    {
        $_SESSION['user_id'] = 7;
        $_SESSION['user_name'] = 'Alice';
        $_SESSION['user_email'] = 'alice@example.test';
        $_SESSION['csrf_token'] = 'token';
        $_GET = ['q' => 'search'];
        $_POST = ['body' => 'value', 'csrf_token' => 'token'];
        $_FILES = ['avatar' => ['name' => 'a.png', 'tmp_name' => '/tmp/a.png', 'error' => UPLOAD_ERR_OK, 'size' => 1]];
        $_COOKIE = ['theme' => 'dark'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['HTTP_X_TEST_HEADER'] = 'header';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $controller = new TestableController();

        self::assertSame(7, $controller->exposeCurrentUserId());
        self::assertSame('Alice', $controller->exposeCurrentUserName());
        self::assertSame('alice@example.test', $controller->exposeCurrentUserEmail());
        self::assertSame('token', $controller->exposeCsrfToken());
        self::assertSame('search', $controller->exposeQueryParam('q'));
        self::assertSame('value', $controller->exposeBodyParam('body'));
        self::assertSame('dark', $controller->exposeCookieParam('theme'));
        self::assertSame('localhost', $controller->exposeServerParam('SERVER_NAME'));
        self::assertSame('header', $controller->exposeHeaderParam('X-Test-Header'));
        self::assertSame('a.png', $controller->exposeUploadedFileParam('avatar')['name']);
        self::assertTrue($controller->exposeIsAjaxRequest());

        $dbMock = $this->createMock(\App\Core\Database::class);
        $dbMock->expects($this->once())->method('fetchOne')->willReturn(['role' => 'admin']);
        $this->bindDatabaseMock($dbMock);

        $controller->callRequireAuth();
        $controller->callRequireAdmin();

        self::assertNull($controller->redirectTarget);

        $_SESSION['csrf_token'] = 'token';
        $controller->callVerifyCsrf();

        $_POST['csrf_token'] = 'bad';
        $this->expectException(RuntimeException::class);
        try {
            $controller->callVerifyCsrf();
        } catch (RuntimeException $exception) {
            self::assertSame(['error' => 'CSRF token mismatch'], $controller->jsonPayload);
            self::assertSame(419, $controller->jsonStatus);
            throw $exception;
        }
    }

    public function testRenderAndRedirectBack(): void
    {
        $controller = new TestableController();
        $controller->callRedirectBack('/fallback');
        self::assertSame('/fallback', $controller->redirectTarget);

        $_SERVER['HTTP_REFERER'] = 'https://example.test/chat?id=5';
        $controller->callRedirectBack('/fallback');
        self::assertSame('/chat?id=5', $controller->redirectTarget);

        $this->expectException(NotFoundException::class);
        $controller->callRender('missing/view');
    }
}
