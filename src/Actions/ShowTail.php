<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class ShowTail
{
    /** @var Queue */
    private $queue;

    /** @var int */
    private $limit;

    public function __construct(Queue $queue, int $limit)
    {
        $this->queue = $queue;
        $this->limit = $limit;
    }

    public function execute(): void
    {
        $events = $this->queue->peekEventsTail($this->limit);
        $count = count($events);

        if ($count === 0) {
            echo "Событий нет.\n";

            return;
        }

        $total = $this->queue->getLength();
        echo "{$count} последних событий из {$total}.\n";

        foreach ($events as $event) {
            echo "Событие {$event->getEventId()}, аккаунт {$event->getAccountId()}\n";
        }
    }
}