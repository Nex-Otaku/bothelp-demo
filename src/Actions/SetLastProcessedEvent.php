<?php

namespace App\Actions;

use App\Components\Queue\Queue;

class SetLastProcessedEvent
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $randomInt = random_int(100000, 200000);
        $this->queue->setLastProcessedEventId(1, $randomInt);
        echo "ID последнего обработанного события установлено в случайное значение: {$randomInt}\n";
    }
}