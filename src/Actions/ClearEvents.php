<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class ClearEvents
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $this->queue->clear();
        echo "Очередь очищена.\n";
    }
}