<?php

namespace App;

use App\Actions\CheckRedis;
use App\Actions\ClearEvents;
use App\Actions\ConsumeEvent;
use App\Actions\CreateEvent;
use App\Actions\GenerateEvents;
use App\Actions\GenerateEventsPack;
use App\Actions\ReadAccountLock;
use App\Actions\ReadLastProcessedEvent;
use App\Actions\ResetAccountLock;
use App\Actions\ResetLastProcessedEvent;
use App\Actions\RunWorker;
use App\Actions\SetAccountLock;
use App\Actions\SetLastProcessedEvent;
use App\Actions\ShowTail;
use App\Components\Console\BreakSignalDetector;
use App\Components\Console\Console;
use App\Components\EventGenerator\EventGenerator;
use App\Components\Filesystem\Filesystem;
use App\Components\Queue\Queue;
use App\Components\Redis\RedisConnection;
use App\Components\Worker\FileLog;
use App\Components\Worker\Worker;

class ConsoleApplication
{
    /** @var RedisConfig */
    private $redisConfig;

    /** @var BreakSignalDetector */
    private $breakSignalDetector;

    /** @var string */
    private $rootPath;

    public function __construct(RedisConfig $redisConfig, string $rootPath)
    {
        $this->redisConfig = $redisConfig;
        $this->rootPath = $rootPath;
        $this->breakSignalDetector = new BreakSignalDetector();
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
        $this->breakSignalDetector->registerSignalHandler();

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

        if ($route === 'generate-events') {
            $this->generateEvents();

            return;
        }

        if ($route === 'generate-events-pack') {
            $this->generateEventsPack();

            return;
        }

        if ($route === 'clear-events') {
            $this->clearEvents();

            return;
        }

        if ($route === 'run-worker') {
            $this->runWorker();

            return;
        }

        if ($route === 'show-tail') {
            $limit = $this->getIntParameter($params, 0) ?? 10;
            $this->showTail($limit);

            return;
        }

        if ($route === 'read-account-lock') {
            $this->readAccountLock();

            return;
        }

        if ($route === 'set-account-lock') {
            $this->setAccountLock();

            return;
        }

        if ($route === 'reset-account-lock') {
            $this->resetAccountLock();

            return;
        }

        if ($route === 'read-last-event') {
            $this->readLastEvent();

            return;
        }

        if ($route === 'set-last-event') {
            $this->setLastEvent();

            return;
        }

        if ($route === 'reset-last-event') {
            $this->resetLastEvent();

            return;
        }

        echo "Действие не найдено: {$route}\n";
    }

    private function listActions(): void
    {
        echo "Доступные действия:\n";
        echo "\thello\n";
        echo "\tcreate-event\n";
        echo "\tconsume-event\n";
        echo "\tcheck-redis\n";
        echo "\tgenerate-events\n";
        echo "\tgenerate-events-pack\n";
        echo "\tclear-events\n";
        echo "\trun-worker\n";
        echo "\tshow-tail [limit]\n";
        echo "\tread-account-lock\n";
        echo "\tset-account-lock\n";
        echo "\treset-account-lock\n";
        echo "\tset-last-event\n";
        echo "\tread-last-event\n";
        echo "\treset-last-event\n";
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

    private function generateEvents(): void
    {
        (new GenerateEvents($this->getEventGenerator(), $this->getQueue()))->execute();
    }

    private function generateEventsPack(): void
    {
        (new GenerateEventsPack($this->getEventGenerator(), $this->getQueue()))->execute();
    }

    private function getEventGenerator(): EventGenerator
    {
        return new EventGenerator($this->getQueue(), $this->breakSignalDetector);
    }

    private function clearEvents(): void
    {
        (new ClearEvents($this->getQueue()))->execute();
    }

    private function runWorker(): void
    {
        (new RunWorker($this->getWorker()))->execute();
    }

    private function getWorker(): Worker
    {
        return new Worker($this->getQueue(), $this->breakSignalDetector, $this->getFileLog());
    }

    private function showTail(int $limit): void
    {
        (new ShowTail($this->getQueue(), $limit))->execute();
    }

    private function getIntParameter(array $params, int $index): ?int
    {
        $value = $params[$index] ?? null;

        if (!is_string($value)) {
            return null;
        }

        if (!ctype_digit($value)) {
            return null;
        }

        return (int)$value;
    }

    private function readAccountLock(): void
    {
        (new ReadAccountLock($this->getQueue()))->execute();
    }

    private function setAccountLock(): void
    {
        (new SetAccountLock($this->getQueue()))->execute();
    }

    private function resetAccountLock(): void
    {
        (new ResetAccountLock($this->getQueue()))->execute();
    }

    private function readLastEvent(): void
    {
        (new ReadLastProcessedEvent($this->getQueue()))->execute();
    }

    private function setLastEvent(): void
    {
        (new SetLastProcessedEvent($this->getQueue()))->execute();
    }

    private function resetLastEvent(): void
    {
        (new ResetLastProcessedEvent($this->getQueue()))->execute();
    }

    private function getFileLog(): FileLog
    {
        return new FileLog($this->getFilesystem(), $this->rootPath);
    }

    private function getFilesystem(): Filesystem
    {
        return new Filesystem();
    }
}