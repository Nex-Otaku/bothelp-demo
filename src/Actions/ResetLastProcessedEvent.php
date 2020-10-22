<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class ResetLastProcessedEvent
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $this->queue->resetLastProcessedEvent(1);

        echo "Очищен ID последнего обработанного события.\n";
    }
}