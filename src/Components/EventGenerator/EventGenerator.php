<?php

namespace App\Components\EventGenerator;

use App\Components\Console\ProgressBar;
use App\Components\Queue\Event;
use App\Components\Queue\Queue;

class EventGenerator
{
    /** @var int */
    private $generatedCount = 0;

    /** @var ProgressBar */
    private $progressBar;

    /** @var int */
    private $accountLimit = 0;

    /** @var int */
    private $eventRowLimit = 0;

    /** @var Queue */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function generate(int $accountLimit, int $eventRowLimit): void
    {
        $this->accountLimit = $accountLimit;
        $this->eventRowLimit = $eventRowLimit;
        $this->generateEvents(true);
    }

    public function generatePack(int $accountLimit, int $eventRowLimit): void
    {
        $this->accountLimit = $accountLimit;
        $this->eventRowLimit = $eventRowLimit;
        $this->generateEvents(false);
    }

    public function getGeneratedCount(): int
    {
        return $this->generatedCount;
    }

    private function generateEvents(bool $sleep): void
    {
        $this->generatedCount = 0;

        if ($this->getTotal() < 1) {
            return;
        }

        $this->updateProgressBar();

        for ($accountId = 1; $accountId <= $this->accountLimit; $accountId++) {
            $this->generateForAccount($accountId);

            if ($sleep) {
                $this->sleep();
            }
        }

        echo "\n";
    }

    private function sleep(): void
    {
        sleep(1);
    }

    private function getTotal(): int
    {
        return $this->accountLimit * $this->eventRowLimit;
    }

    private function updateProgressBar(): void
    {
        $this->getProgressBar()->showProgress($this->generatedCount, $this->getTotal());
    }

    private function getProgressBar(): ProgressBar
    {
        if ($this->progressBar === null) {
            $this->progressBar = new ProgressBar('Создаём события:');
        }

        return $this->progressBar;
    }

    private function generateForAccount(int $accountId): void
    {
        for ($eventIterator = 0; $eventIterator < $this->eventRowLimit; $eventIterator++) {
            $this->generatedCount++;
            $this->queue->add(new Event($this->generatedCount, $accountId));
            $this->updateProgressBar();
        }
    }
}