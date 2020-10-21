<?php

namespace App\Components\EventGenerator;

use App\Components\Console\ProgressBar;
use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class EventGenerator
{
    private $generatedCount = 0;

    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function generate(int $accountLimit, int $eventRowLimit): void
    {
        $total = $accountLimit * $eventRowLimit;

        if ($total < 1) {
            return;
        }

        $progressBar = new ProgressBar('Создаём события:');
        $progressBar->showProgress(0, $total);

        for ($accountId = 1; $accountId <= $accountLimit; $accountId++) {
            for ($eventIterator = 0; $eventIterator < $eventRowLimit; $eventIterator++) {
                $this->generatedCount++;
                $this->queue->add(new Event($this->generatedCount, $accountId));
                $progressBar->showProgress($this->generatedCount, $total);
            }
        }

        echo "\n";
    }

    public function getGeneratedCount(): int
    {
        return $this->generatedCount;
    }
}