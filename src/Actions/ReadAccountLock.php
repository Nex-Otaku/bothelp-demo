<?php

namespace App\Actions;

use App\Components\Queue\AccountProcessingInfo;
use App\Components\Queue\Queue;

class ReadAccountLock
{
    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $accountProcessingInfo = $this->queue->readAccountProcessingChannel(1);

        if ($accountProcessingInfo === null) {
            echo "Блокировки нет.\n";

            return;
        }

        echo "Блокировка: аккаунт {$accountProcessingInfo->getAccountId()}, воркер {$accountProcessingInfo->getWorkerId()}, timestamp {$accountProcessingInfo->getAcquiredAt()}\n";
    }
}