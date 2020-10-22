<?php

namespace App\Actions;

use App\Components\Queue\AccountProcessingInfo;
use App\Components\Queue\Queue;

class ResetAccountLock
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $this->queue->resetAccountLock(1);

        echo "Блокировка удалена.\n";
    }
}