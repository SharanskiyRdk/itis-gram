<?php

namespace App\Core;

use App\Exceptions\ConfigException;
use App\Exceptions\FileNotFoundException;
use Dotenv\Dotenv;

class Config
{
    private array $data = [];

    /**
     * @throws ConfigException
     * @throws FileNotFoundException
     */
    public function __construct(string $pathToEnv)
    {
        $this->load($pathToEnv);
    }

    /**
     * @throws FileNotFoundException
     * @throws ConfigException
     */
    private function load(string $pathToEnv): void {

        if (!file_exists($pathToEnv)) {
            throw new FileNotFoundException('Файл .env не найден');
        }

        $dir = dirname($pathToEnv);
        $file = basename($pathToEnv);

        $dotenv = Dotenv::createImmutable($dir, $file);
        $dotenv->load();

        $this->data = $_ENV;
        $this->validate();
    }

    /**
     * @throws ConfigException
     */
    private function validate(): void
    {
        $required = [
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'APP_ENV',
            'APP_DEBUG',
            'WS_HOST',
            'WS_PORT'
        ];

        foreach ($required as $key) {
            if (!isset($this->data[$key]) || $this->data[$key] === '') {
                throw new ConfigException("Отсутствует обязательный параметр: {$key}");
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}