<?php

namespace App\Actions;

use App\Components\EventGenerator\EventGenerator;
use App\Components\Queue\Queue;

class GenerateManyEvents
{
    private const ACCOUNTS_LIMIT = 1000;
    private const EVENT_ROW_LIMIT = 10;

    /** @var EventGenerator */
    private $eventGenerator;

    /** @var Queue */
    private $queue;

    public function __construct(EventGenerator $eventGenerator, Queue $queue)
    {
        $this->eventGenerator = $eventGenerator;
        $this->queue = $queue;
    }

    public function execute(): void
    {
        $this->eventGenerator->generate(self::ACCOUNTS_LIMIT, self::EVENT_ROW_LIMIT);
        echo "Создано событий: {$this->eventGenerator->getGeneratedCount()}.\n";
        echo "Всего событий в очереди: {$this->queue->getLength()}.\n";
    }
}