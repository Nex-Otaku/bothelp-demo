<?php

namespace App\Actions;

use App\Helpers\Json;
use App\Components\Redis\RedisConnection;

class CheckRedis
{
    /** @var RedisConnection */
    private $redisConnection;

    public function __construct(RedisConnection $redisConnection)
    {
        $this->redisConnection = $redisConnection;
    }

    public function execute(): void
    {
        $this->checkIsAlive();
        $this->checkWrite();
    }

    private function checkIsAlive(): void
    {
        echo 'Проверяем, что Redis доступен...';

        if (!$this->redisConnection->isAlive()) {
            echo " ОШИБКА - Подключение недоступно.\n";

            return;
        }

        echo " OK\n";
    }

    private function checkWrite(): void
    {
        echo "\n";
        $keyY = $this->generateRandomKey();
        echo "Ключ: \"{$keyY}\"\n";
        $valueY = ['name' => 'Kate', 'age' => 18, 'isManager' => false, 'pets' => ['fish', 'tortoise']];
        $encodedValueY = (new Json())->encode($valueY);
        echo "Значение: {$encodedValueY}\n";
        $expireSeconds = 0;
        $this->redisConnection->set($keyY, $encodedValueY, $expireSeconds);
        echo "Записали значение.\n";
        echo "OK\n";

        echo "\n";
        $rawValueY = $this->redisConnection->get($keyY);
        echo "Значение по ключу \"{$keyY}\" (RAW): {$rawValueY}\n";
        $decodedValueY = ($rawValueY !== null) ? (new Json)->decode($rawValueY) : null;
        echo "Значение по ключу \"{$keyY}\" (Декодировано):\n";
        print_r($decodedValueY);

        if ($encodedValueY !== $rawValueY) {
            echo "Ошибка! Значение, прочитанное из Redis, отличается от записанного.\n";

            return;
        }

        echo "OK\n";

        echo "\n";
        echo "Удаляем значение по ключу \"{$keyY}\":\n";
        $this->redisConnection->delete($keyY);
        echo "OK\n";
    }

    private function generateRandomKey(): string
    {
        return 'check-redis-' . md5((string)rand());
    }
}