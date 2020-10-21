<?php

namespace App\Components\Queue;

use App\Components\Redis\RedisConnection;

class Queue
{
    private const KEY_EVENT_QUEUE = 'event-queue';

    /** @var RedisConnection */
    private $redisConnection;

    public function __construct(RedisConnection $redisConnection)
    {
        $this->redisConnection = $redisConnection;
    }

    public function add(Event $event): void
    {
        $this->redisConnection->appendToList(self::KEY_EVENT_QUEUE, [$event->toString()]);
    }

    public function consume(): ?Event
    {
        $item = $this->redisConnection->popFromListTail(self::KEY_EVENT_QUEUE);

        if (!is_string($item)) {
            return null;
        }

        return Event::fromString($item);
    }
}