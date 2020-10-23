<?php

namespace App\Components\Queue;

use App\Components\Redis\RedisConnection;

class Queue
{
    private const KEY_EVENT_QUEUE        = 'event-queue';
    private const KEY_ACCOUNT_PROCESSING = 'account-processing-';
    private const KEY_LAST_PROCESSED_EVENT = 'last-processed-event-for-account-';

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
    public function peekEventsHead(int $limit): array
    {
        $items = $this->redisConnection->getListHead(self::KEY_EVENT_QUEUE, $limit);

        return $this->extractEvents($items);
    }

    /**
     * @param int $limit
     * @return Event[]
     */
    public function peekEventsTail(int $limit): array
    {
        $items = $this->redisConnection->getListTail(self::KEY_EVENT_QUEUE, $limit);

        return $this->extractEvents($items);
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

    public function getLastProcessedEventId(int $accountId): ?int
    {
        $key = $this->getAccountLastEventKey($accountId);
        $value = $this->redisConnection->get($key);

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \RuntimeException("Не удалось извлечь данные по ключу \"{$key}\"");
        }

        if (!ctype_digit($value)) {
            throw new \RuntimeException("Строка не представляет целое число. Ключ \"{$key}\". Значение: {$value}");
        }

        return (int)$value;
    }

    public function setLastProcessedEventId(int $accountId, int $eventId): void
    {
        $this->redisConnection->set($this->getAccountLastEventKey($accountId), (string)$eventId);
    }

    private function getAccountLastEventKey(int $accountId): string
    {
        return self::KEY_LAST_PROCESSED_EVENT . (string)$accountId;
    }

    public function resetLastProcessedEvent(int $accountId): void
    {
        $this->redisConnection->delete($this->getAccountLastEventKey($accountId));
    }

    /**
     * @param string[] $items
     * @return Event[]
     */
    private function extractEvents(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $result []= Event::fromString($item);
        }

        return $result;
    }

    public function removeEvent(Event $event): bool
    {
        return $this->redisConnection->removeFromList(self::KEY_EVENT_QUEUE, $event->toString());
    }
}