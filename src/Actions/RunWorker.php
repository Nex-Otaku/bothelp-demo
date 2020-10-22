<?php

namespace App\Actions;

use App\Components\Worker\Worker;

class RunWorker
{
    /** @var Worker */
    private $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    public function execute(): void
    {
        $this->worker->processQueue();
    }
}