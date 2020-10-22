<?php

namespace App\Components\Queue;

use App\Components\Redis\RedisConnection;

class Queue
{
    private const KEY_EVENT_QUEUE        = 'event-queue';
    private const KEY_ACCOUNT_PROCESSING = 'account-processing-';

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

    /**
     * @param int $limit
     * @return Event[]
     */
    public function peekEventsTail(int $limit): array
    {
        $items = $this->redisConnection->getListTail(self::KEY_EVENT_QUEUE, $limit);
        $result = [];

        foreach ($items as $item) {
            $result []= Event::fromString($item);
        }

        return $result;
    }

    public function acquireAccountProcessingChannel(AccountProcessingInfo $accountProcessingInfo): bool
    {
        return $this->redisConnection->acquireLock(
            $this->getAccountChannelKey($accountProcessingInfo->getAccountId()),
            $accountProcessingInfo->toString()
        );
    }

    public function readAccountProcessingChannel(int $accountId): ?AccountProcessingInfo
    {
        $lock = $this->redisConnection->readLock($this->getAccountChannelKey($accountId));

        if ($lock === null) {
            return null;
        }

        return AccountProcessingInfo::fromString($lock);
    }

    public function resetAccountLock(int $accountId): void
    {
        $this->redisConnection->resetLock($this->getAccountChannelKey($accountId));
    }

    private function getAccountChannelKey(int $accountId): string
    {
        return self::KEY_ACCOUNT_PROCESSING . (string)$accountId;
    }
}