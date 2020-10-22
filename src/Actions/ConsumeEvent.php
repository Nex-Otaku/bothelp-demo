<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class ConsumeEvent
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $event = $this->queue->consume();

        if ($event === null) {
            echo "Событий на обработку нет.\n";

            return;
        }

        echo "Событие: Аккаунт {$event->getAccountId()}, событие {$event->getEventId()}.\n";
    }
}