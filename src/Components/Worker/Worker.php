<?php

namespace App\Components\Worker;

use App\Components\Console\BreakSignalDetector;
use App\Components\Queue\AccountProcessingInfo;
use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class Worker
{
    private const EVENT_FETCH_LIMIT = 150;

    /** @var Queue */
    private $queue;

    /** @var BreakSignalDetector */
    private $breakSignalDetector;

    /** @var FileLog */
    private $fileLog;

    /** @var string */
    private $workerId;

    /** @var bool */
    private $isLockAcquired = false;

    /** @var int */
    private $lockedAccountId = 0;

    public function __construct(
        Queue $queue,
        BreakSignalDetector $breakSignalDetector,
        FileLog $fileLog
    )
    {
        $this->queue = $queue;
        $this->breakSignalDetector = $breakSignalDetector;
        $this->fileLog = $fileLog;
    }

    public function processQueue(): void
    {
        while ($this->isWorkersEnabled()) {
            $this->work();
        }
    }

    private function isWorkersEnabled(): bool
    {
        return !$this->breakSignalDetector->isTerminated();
    }

    private function work(): void
    {
        $accountId = $this->acquireAccountLock();

        if ($accountId === null) {
            $this->log("Работы нет. Сплю секунду...");
            $this->sleep();

            return;
        }

        $event = $this->fetchEventForAccount($accountId);

        if ($event === null) {
            $this->releaseAccountLock();
            $this->log("Не удалось получить событие для обработки. Сплю секунду...");
            $this->sleep();

            return;
        }

        $this->setLastProcessedEventId($event);
        $this->processEvent($event);
        $this->releaseAccountLock();
    }

    private function sleep(): void
    {
        sleep(1);
    }

    private function canProcessEvent(Event $event): bool
    {
        return $event->getEventId() > $this->getLastProcessedEventId($event->getAccountId());
    }

    private function processEvent(Event $event): void
    {
        $this->sleep();
        $this->log("Обработано: аккаунт {$event->getAccountId()}, событие {$event->getEventId()}");
    }

    private function log(string $message): void
    {
        $this->logToScreen($message);
        $this->logToFile($message);
    }

    private function logToScreen(string $message): void
    {
        echo "{$message}\n";
    }

    private function logToFile(string $message): void
    {
        $time = date('Y-m-d H:i:s');
        $workerId = $this->getWorkerId();
        $this->fileLog->write("{$time} {$workerId} | {$message}");
    }

    private function getWorkerId(): string
    {
        if ($this->workerId === null) {
            $this->workerId = 'worker-' . md5((string)rand());
        }

        return $this->workerId;
    }

    private function releaseAccountLock(): void
    {
        if (!$this->isLockAcquired) {
            return;
        }

        $this->isLockAcquired = false;
        $this->queue->resetAccountLock($this->lockedAccountId);
        $this->lockedAccountId = 0;
    }

    private function acquireLock(int $accountId): bool
    {
        if ($this->isLockAcquired) {
            throw new \LogicException('Чтобы взять новую блокировку, нужно освободить предыдущую');
        }

        $accountProcessingInfo = new AccountProcessingInfo(
            $accountId,
            $this->getWorkerId(),
            time()
        );

        $acquired = $this->queue->acquireAccountProcessingChannel($accountProcessingInfo);

        if ($acquired) {
            $this->isLockAcquired = true;
            $this->lockedAccountId = $accountProcessingInfo->getAccountId();
        }

        return $acquired;
    }

    private function getLastProcessedEventId(int $accountId): int
    {
        return $this->queue->getLastProcessedEventId($accountId) ?? 0;
    }

    private function setLastProcessedEventId(Event $event)
    {
        $this->queue->setLastProcessedEventId($event->getAccountId(), $event->getEventId());
    }

    private function acquireAccountLock():? int
    {
        $accountIds = $this->searchAccountIds();

        foreach ($accountIds as $accountId) {
            if ($this->acquireLock($accountId)) {
                return $accountId;
            }
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function searchAccountIds(): array
    {
        $events = $this->queue->peekEventsHead(self::EVENT_FETCH_LIMIT);
        $accountIds = [];

        foreach ($events as $event) {
            $accountIds []= $event->getAccountId();
        }

        return $accountIds;
    }

    private function fetchEventForAccount(int $accountId): ?Event
    {
        $event = $this->searchEventForAccount($accountId);

        if ($event === null) {
            return null;
        }

        if (!$this->canProcessEvent($event)) {
            return null;
        }

        $removed = $this->queue->removeEvent($event);

        if (!$removed) {
            return null;
        }

        return $event;
    }

    private function searchEventForAccount(int $accountId): ?Event
    {
        $events = $this->queue->peekEventsHead(self::EVENT_FETCH_LIMIT);

        foreach ($events as $event) {
            if ($event->getAccountId() !== $accountId) {
                continue;
            }

            return $event;
        }

        return null;
    }
}