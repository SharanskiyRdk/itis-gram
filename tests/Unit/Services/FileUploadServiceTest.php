<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FileUploadService;
use PHPUnit\Framework\TestCase;

class FileUploadServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'itis-gram-tests-' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testValidateFileDetectsMimeAndSize(): void
    {
        $service = new FileUploadService();
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('uploadDir');
        $property->setAccessible(true);
        $property->setValue($service, dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

        $file = $this->createTempFile('image/png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5f9nUAAAAASUVORK5CYII='));
        self::assertTrue($service->validateFile($file, 'avatar'));
        self::assertSame('image', $service->detectMessageFileType($file));

        $textFile = $this->createTempFile('text/plain', 'plain text');
        self::assertSame('file', $service->detectMessageFileType($textFile));
        self::assertFalse($service->validateFile($textFile, 'image'));

        self::assertMatchesRegularExpression('/^file_[0-9a-f]+\.[0-9]+\.[a-z0-9]+$/i', $service->generateFileName('file.txt'));
    }

    public function testUploadAvatarCopiesFileAndDeleteFileRemovesIt(): void
    {
        $service = new FileUploadService();
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('uploadDir');
        $property->setAccessible(true);
        $property->setValue($service, dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

        $file = $this->createTempFile('image/png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5f9nUAAAAASUVORK5CYII='));
        $avatar = $service->uploadAvatar($file);

        self::assertNotNull($avatar);
        self::assertStringStartsWith('/uploads/avatars/', $avatar);

        $saved = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($avatar, '/');
        self::assertFileExists($saved);
        self::assertTrue($service->deleteFile($avatar));
        self::assertFileDoesNotExist($saved);
    }

    private function createTempFile(string $mime, string $content): array
    {
        $path = tempnam($this->tempDir, 'file_');
        file_put_contents($path, $content);

        return [
            'name' => 'sample.' . ($mime === 'image/png' ? 'png' : 'txt'),
            'type' => $mime,
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($content),
        ];
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
