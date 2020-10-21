<?php

namespace App\Actions;

use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class CreateEvent
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $this->queue->add(new Event(1, 1));
        echo "Событие создано.\n";
    }
}