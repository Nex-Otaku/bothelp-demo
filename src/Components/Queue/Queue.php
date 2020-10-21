<?php

namespace App\Components\Queue;

use App\Components\Redis\RedisConnection;

class Queue
{
    private const QUEUE_REDIS_KEY = 'event-queue';

    /** @var RedisConnection */
    private $redisConnection;

    public function __construct(RedisConnection $redisConnection)
    {
        $this->redisConnection = $redisConnection;
    }

    public function add(Event $event): void
    {
        $this->redisConnection->appendToList(self::QUEUE_REDIS_KEY, [$event->toString()]);
    }

    public function getFirstEvent(): void
    {
        // STUB
    }
}