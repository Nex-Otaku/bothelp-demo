<?php

namespace App\Components\Worker;

use App\Components\Filesystem\Filesystem;

class FileLog
{
    /** @var string */
    private $rootPath;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem, string $rootPath)
    {
        $this->filesystem = $filesystem;
        $this->rootPath = $rootPath;
    }

    public function write(string $message)
    {
        $this->checkLogDirectory();
        $logMessage = "{$message}\n";
        $this->filesystem->appendToFile($this->getLogFilePath(), $logMessage);
    }

    private function getLogFilePath(): string
    {
        return $this->getLogDirectory() . DIRECTORY_SEPARATOR . 'log.txt';
    }

    private function checkLogDirectory(): void
    {
        $logDir = $this->getLogDirectory();

        if (!$this->filesystem->existsDirectory($logDir)) {
            $this->filesystem->createDirectory($logDir);
        }
    }

    private function getLogDirectory(): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'logs';
    }
}