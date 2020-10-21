<?php

namespace App;

use App\Actions\CheckRedis;
use App\Actions\ConsumeEvent;
use App\Actions\CreateEvent;
use App\Actions\GenerateManyEvents;
use App\Components\Console\Console;
use App\Components\EventGenerator\EventGenerator;
use App\Components\Queue\Queue;
use App\Components\Redis\RedisConnection;

class ConsoleApplication
{
    /** @var RedisConfig */
    private $redisConfig;

    public function __construct(RedisConfig $redisConfig)
    {
        $this->redisConfig = $redisConfig;
    }

    public function run(): int
    {
        $console = new Console();
        [$route, $params] = $console->resolve();

        if ($route === '') {
            $this->listActions();
        } else {
            $this->runAction($route, $params);
        }

        return 0;
    }

    public function runAction(string $route, array $params = []): void
    {
        if ($route === 'hello') {
            echo "Hello World!\n";

            return;
        }

        if ($route === 'create-event') {
            $this->createEvent();

            return;
        }

        if ($route === 'check-redis') {
            $this->checkRedis();

            return;
        }

        if ($route === 'consume-event') {
            $this->consumeEvent();

            return;
        }

        if ($route === 'generate-many-events') {
            $this->generateManyEvents();

            return;
        }

        echo "Not found action: {$route}\n";
    }

    private function listActions(): void
    {
        echo "Доступные действия:\n";
        echo "\thello\n";
        echo "\tcreate-event\n";
        echo "\tconsume-event\n";
        echo "\tcheck-redis\n";
        echo "\tgenerate-many-events\n";
    }

    private function createEvent(): void
    {
        (new CreateEvent($this->getQueue()))->execute();
    }

    private function getQueue(): Queue
    {
        return new Queue($this->getRedis());
    }

    private function checkRedis(): void
    {
        (new CheckRedis($this->getRedis()))->execute();
    }

    private function getRedis(): RedisConnection
    {
        return new RedisConnection($this->redisConfig);
    }

    private function consumeEvent(): void
    {
        (new ConsumeEvent($this->getQueue()))->execute();
    }

    private function generateManyEvents(): void
    {
        (new GenerateManyEvents($this->getEventGenerator(), $this->getQueue()))->execute();
    }

    private function getEventGenerator(): EventGenerator
    {
        return new EventGenerator($this->getQueue());
    }
}