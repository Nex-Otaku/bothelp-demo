<?php

namespace App\Components\Worker;

use App\Components\Console\BreakSignalDetector;
use App\Components\Queue\AccountProcessingInfo;
use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class Worker
{
    private const EVENT_FETCH_LIMIT = 1500;

    /** @var Queue */
    private $queue;

    /** @var BreakSignalDetector */
    private $breakSignalDetector;

    /** @var string */
    private $workerId;

    /** @var bool */
    private $isLockAcquired = false;

    /** @var int */
    private $lockedAccountId = 0;

    public function __construct(Queue $queue, BreakSignalDetector $breakSignalDetector)
    {
        $this->queue = $queue;
        $this->breakSignalDetector = $breakSignalDetector;
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
        $event = $this->fetchEvent(0);

        if ($event === null) {
            $this->log("Работы нет. Сплю секунду...");
            $this->sleep();

            return;
        }

        if (!$this->canProcessEvent($event)) {
            $this->queue->putBack($event);
            $this->log('Событие занято. Жду секунду...');
            $this->sleep();

            return;
        }

        $this->processEvent($event);
        $this->releaseLock();
    }

    private function fetchEvent(int $level): ?Event
    {
        $event = $this->queue->consume();

        if ($event === null) {
            return null;
        }

        $level++;

        if ($level >= self::EVENT_FETCH_LIMIT) {
            return $event;
        }

        if (!$this->canProcessEvent($event)) {
            $oldEvent = $event;
            $event = $this->fetchEvent($level);
            $this->queue->putBack($oldEvent);
        }

        return $event;
    }

    private function sleep(): void
    {
        sleep(1);
    }

    private function canProcessEvent(Event $event): bool
    {
        if ($this->isLockAcquired) {
            if ($event->getAccountId() !== $this->lockedAccountId) {
                throw new \LogicException('Чтобы взять новую блокировку, нужно освободить предыдущую');
            }

            return true;
        }

        $accountProcessingInfo = new AccountProcessingInfo(
            $event->getAccountId(),
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

    private function processEvent(Event $event): void
    {
        $this->sleep();
        $this->log("Обработано: аккаунт {$event->getAccountId()}, событие {$event->getEventId()}");
    }

    private function log(string $message): void
    {
        echo "{$message}\n";
    }

    private function getWorkerId(): string
    {
        if ($this->workerId === null) {
            $this->workerId = 'worker-' . md5((string)rand());
        }

        return $this->workerId;
    }

    private function releaseLock(): void
    {
        if (!$this->isLockAcquired) {
            return;
        }

        $this->isLockAcquired = false;
        $this->queue->resetAccountLock($this->lockedAccountId);
        $this->lockedAccountId = 0;
    }
}