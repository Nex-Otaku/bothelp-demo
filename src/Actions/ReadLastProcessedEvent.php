<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class ReadLastProcessedEvent
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $eventId = $this->queue->getLastProcessedEventId(1);

        if ($eventId === null) {
            echo "События нет.\n";

            return;
        }

        echo "Последнее обработанное событие: {$eventId}\n";
    }
}