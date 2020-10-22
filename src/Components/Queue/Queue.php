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

    public function putBack(Event $event): void
    {
        $this->redisConnection->prependToList(self::KEY_EVENT_QUEUE, [$event->toString()]);
    }

    public function consume(): ?Event
    {
        $item = $this->redisConnection->popFromListHead(self::KEY_EVENT_QUEUE);

        if (!is_string($item)) {
            return null;
        }

        return Event::fromString($item);
    }

    public function getLength(): int
    {
        return $this->redisConnection->getListLength(self::KEY_EVENT_QUEUE);
    }

    public function clear(): void
    {
        $this->redisConnection->delete(self::KEY_EVENT_QUEUE);
    }
}