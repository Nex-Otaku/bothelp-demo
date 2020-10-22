<?php

namespace App\Actions;

use App\Components\Queue\AccountProcessingInfo;
use App\Components\Queue\Queue;

class SetAccountLock
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $accountProcessingInfo = new AccountProcessingInfo(1, 'test-worker-id', time());

        if ($this->queue->acquireAccountProcessingChannel($accountProcessingInfo)) {
            echo "Блокировка установлена.\n";
        } else {
            echo "Блокировка не установлена.\n";
        }
    }
}