<?php

namespace App\Components\Redis;

use App\RedisConfig;
use Predis\Client;

class RedisConnection
{
    /** @var RedisConfig */
    private $redisConfig;

    /** @var Client */
    private $client;

    public function __construct(
        RedisConfig $redisConfig
    )
    {
        $this->redisConfig = $redisConfig;
    }

    public function get(string $key): ?string
    {
        $result = $this->getClient()->get($key);
        if (!is_string($result)) {
            return null;
        }

        return $result;
    }

    public function set(string $key, string $value, int $expireSeconds = 0): void
    {
        // Время жизни записи устанавливается при записи в хранилище, методом EXPIRE.
        // https://redis.io/commands/expire
        // Очистка производится автоматически средствами самого Redis, раз в 10 секунд.

        if ($expireSeconds !== 0) {
            $this->getClient()->setex($key, $expireSeconds, $value);
            return;
        }

        $this->getClient()->set($key, $value);
    }

    public function delete(string $key): void
    {
        $this->getClient()->del([$key]);
    }

    public function isAlive(): bool
    {
        $message = 'Hello!';
        $answer = $this->getClient()->ping($message);
        return $answer === $message;
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            // https://github.com/nrk/predis/wiki/Connection-Parameters
            $this->client = new Client([
                'scheme' => 'tcp',
                'host'   => $this->redisConfig->host,
                'port'   => $this->redisConfig->port,
                'database' => $this->redisConfig->dbId,
                'password' => $this->redisConfig->password,
            ]);
        }

        return $this->client;
    }
}