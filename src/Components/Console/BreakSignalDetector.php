<?php

namespace App\Components\Console;

class BreakSignalDetector
{
    /** @var bool */
    private $isTerminated = false;

    public function registerSignalHandler(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, [$this, 'terminate']);
        pcntl_signal(SIGTERM, [$this, 'terminate']);
    }

    public function terminate(): void
    {
        $this->isTerminated = true;
    }

    public function isTerminated(): bool
    {
        return $this->isTerminated;
    }
}