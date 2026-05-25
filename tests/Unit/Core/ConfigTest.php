<?php

namespace Tests\Unit\Core;

use App\Core\Config;
use App\Exceptions\ConfigException;
use App\Exceptions\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLoadsValuesFromEnvFile(): void
    {
        $envFile = tempnam(sys_get_temp_dir(), 'itisgram_env_');
        file_put_contents($envFile, implode("\n", [
            'DB_HOST=localhost',
            'DB_PORT=5432',
            'DB_NAME=itisgram',
            'DB_USER=postgres',
            'DB_PASSWORD=secret',
            'APP_ENV=testing',
            'APP_DEBUG=false',
            'WS_HOST=127.0.0.1',
            'WS_PORT=8080',
        ]));

        try {
            $config = new Config($envFile);

            self::assertSame('localhost', $config->get('DB_HOST'));
            self::assertSame('testing', $config->get('APP_ENV'));
            self::assertSame('fallback', $config->get('UNKNOWN', 'fallback'));
        } finally {
            @unlink($envFile);
        }
    }

    public function testThrowsWhenEnvFileMissing(): void
    {
        $this->expectException(FileNotFoundException::class);

        new Config(sys_get_temp_dir() . '/missing_' . uniqid() . '.env');
    }

    public function testThrowsWhenRequiredKeyMissing(): void
    {
        $this->clearConfigEnvironment();

        $envFile = tempnam(sys_get_temp_dir(), 'itisgram_env_');
        file_put_contents($envFile, implode("\n", [
            'DB_HOST=localhost',
            'DB_PORT=5432',
            'DB_NAME=itisgram',
            'DB_USER=postgres',
            'DB_PASSWORD=secret',
            'APP_ENV=testing',
            'APP_DEBUG=false',
            'WS_HOST=127.0.0.1',
        ]));

        try {
            $this->expectException(ConfigException::class);
            new Config($envFile);
        } finally {
            @unlink($envFile);
        }
    }

    private function clearConfigEnvironment(): void
    {
        foreach (
            ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'APP_ENV', 'APP_DEBUG', 'WS_HOST', 'WS_PORT'] as $key
        ) {
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}
