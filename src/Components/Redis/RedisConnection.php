<?php

namespace App\Components\Redis;

use App\RedisConfig;
use http\Exception\RuntimeException;
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

    /**
     * @param string   $key
     * @param string[] $values
     */
    public function appendToList(string $key, array $values): void
    {
        $this->getClient()->rpush($key, $values);
    }

    /**
     * @param string   $key
     * @param string[] $values
     */
    public function prependToList(string $key, array $values): void
    {
        $this->getClient()->lpush($key, $values);
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

    public function popFromListTail(string $key): ?string
    {
        return $this->getClient()->rpop($key);
    }

    public function popFromListHead(string $key): ?string
    {
        return $this->getClient()->lpop($key);
    }

    public function getListLength(string $key): int
    {
        return $this->getClient()->llen($key);
    }

    public function getListHead(string $key, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return $this->getClient()->lrange($key, 0, $limit - 1);
    }

    public function getListTail(string $key, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return $this->getClient()->lrange($key, -$limit, -1);
    }

    public function acquireLock(string $key, string $value): bool
    {
        $result = $this->getClient()->setnx($key, $value);

        return $result === 1;
    }

    public function resetLock(string $key): void
    {
        $this->delete($key);
    }

    public function readLock(string $key): ?string
    {
        return $this->get($key);
    }

    public function removeFromList(string $key, string $value): bool
    {
        $result = $this->getClient()->lrem($key, 0, $value);

        return $result > 0;
    }
}