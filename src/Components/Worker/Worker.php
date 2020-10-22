<?php

namespace App\Components\Worker;

use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class Worker
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function processQueue(): void
    {
        while ($this->isWorkersEnabled()) {
            $this->work();
        }
    }

    private function isWorkersEnabled(): bool
    {
        // STUB Сделать управление воркерами, чтобы корректно гасить их.
        return true;
    }

    private function work(): void
    {
        $event = $this->fetchEvent();

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
    }

    private function fetchEvent(): ?Event
    {
        $event = $this->queue->consume();

        if ($event === null) {
            return null;
        }

        if (!$this->canProcessEvent($event)) {
            $oldEvent = $event;
            $event = $this->queue->consume();
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
        // STUB
        return true;
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
}