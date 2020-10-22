<?php

namespace App\Components\Filesystem;

class Filesystem
{
    /**
     * @param string $path
     */
    public function createDirectory(string $path): void
    {
        /**
         * Проверяем, существует ли папка. Если нет, то пытаемся ее создать, потом проверяем, создалась ли.
         * Не убирать первую проверку, она важна.
         */
        if (is_dir($path)) {
            return;
        }

        $oldMask = umask(0);

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            umask($oldMask);
            throw new \RuntimeException("Не удалось создать папку \"{$path}\"");
        }

        umask($oldMask);
    }

    /**
     * @param string $filePath
     */
    public function createDirectoryForFile(string $filePath): void
    {
        $dirname = dirname($filePath);
        $this->createDirectory($dirname);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function writeFile(string $filePath, string $content): void
    {
        $this->createDirectoryForFile($filePath);
        $result = file_put_contents($filePath, $content);

        if ($result === false) {
            throw new \RuntimeException("Не удалось записать файл \"{$filePath}\"");
        }
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function appendToFile(string $filePath, string $content): void
    {
        $this->createDirectoryForFile($filePath);
        $result = file_put_contents($filePath, $content, FILE_APPEND);

        if ($result === false) {
            throw new \RuntimeException("Не удалось добавить текст в конец файла \"{$filePath}\"");
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isReadableFile(string $path): bool
    {
        return file_exists($path)
            && is_file($path)
            && is_readable($path);
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function readFile(string $path): ?string
    {
        try {
            $content = file_get_contents($path);
        } catch (\Throwable $throwable) {
            return null;
        }

        if (!is_string($content)) {
            return null;
        }

        return $content;
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function renameFile(string $source, string $destination): void
    {
        try {
            $result = rename($source, $destination);
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($result === false) {
            throw new \RuntimeException("Не удалось переименовать \"{$source}\" в \"{$destination}\"");
        }
    }

    /**
     * @return string
     */
    public function getCurrentDirectory(): string
    {
        $currentDirectory = getcwd();

        if (!is_string($currentDirectory)) {
            throw new \RuntimeException('Не удалось определить текущую директорию');
        }

        return $currentDirectory;
    }

    /**
     * @param string $path
     */
    public function deleteFile(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $result = unlink($path);

        if (!$result) {
            throw new \RuntimeException("Не удалось удалить файл: \"{$path}\"");
        }
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function existsFile(string $path): bool
    {
        return file_exists($path)
            && is_file($path);
    }

    public function isFile(string $path): bool
    {
        return $this->existsFile($path);
    }

    public function existsDirectory(string $path): bool
    {
        return file_exists($path)
            && is_dir($path);
    }

    public function isDirectory(string $path): bool
    {
        return $this->existsDirectory($path);
    }

    /**
     * @param string $path
     * @return array
     */
    public function listFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $entries = scandir($path);
        $filtered = array_diff($entries, ['.', '..']);
        // После фильтрации, нужно прогнать массив через "array_values", чтобы обнулить числовые индексы.
        return array_values($filtered);
    }

    public function searchFiles(string $path, string $mask): array
    {
        $contents = $this->getDirectoryContents($path, false);

        return $this->filterByMask($contents, $mask);
    }

    public function searchFilesRecursively(string $path, string $mask): array
    {
        $contents = $this->getDirectoryContents($path, true);

        return $this->filterByMask($contents, $mask);
    }

    private function getDirectoryContents(string $path, bool $recursive): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $entries = scandir($path);
        $filtered = array_diff($entries, ['.', '..']);

        $fullPathEntries = array_map(function ($entry) use ($path) {
            return $path . DIRECTORY_SEPARATOR . $entry;
        }, $filtered);

        if ($recursive) {
            $subEntries = [];

            foreach ($fullPathEntries as $entry) {
                if (!$this->isDirectory($entry)) {
                    continue;
                }

                $subEntries[] = $this->getDirectoryContents($entry, true);
            }

            $fullPathEntries = array_merge($fullPathEntries, ...$subEntries);
        }

        return $fullPathEntries;
    }

    private function getLastPathPart(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return '';
        }

        $delimiter = '';

        if ($this->contains($trimmed, '\\')) {
            $delimiter = '\\';
        }

        if ($this->contains($trimmed, '/')) {
            $delimiter = '/';
        }

        if ($delimiter === '') {
            return $trimmed;
        }

        $parts = explode(DIRECTORY_SEPARATOR, $trimmed);

        $filtered = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);

            if ($trimmed === '') {
                continue;
            }

            $filtered[] = $part;
        }

        if (count($filtered) === 0) {
            return '';
        }

        return array_pop($filtered);
    }

    private function filterByMask(array $entries, string $mask): array
    {
        $result = [];

        foreach ($entries as $entry) {
            $name = $this->getLastPathPart($entry);

            if ($this->startsWith($mask, '*')) {
                $stripped = $this->stripPrefix($mask, '*');
                if ($this->endsWith($name, $stripped)) {
                    $result[] = $entry;
                }
            } else if ($this->endsWith($mask, '*')) {
                $stripped = $this->stripSuffix($mask, '*');
                if ($this->startsWith($name, $stripped)) {
                    $result[] = $entry;
                }
            } else if ($name === $mask) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private function startsWith(string $text, string $with): bool
    {
        $bytes = strlen($with);

        if ($bytes === 0) {
            return true;
        }

        return strncmp($text, $with, $bytes) === 0;
    }

    private function endsWith(string $text, string $with): bool
    {
        if (!$bytes = strlen($with)) {
            return true;
        }

        if (strlen($text) < $bytes) {
            return false;
        }

        return substr_compare($text, $with, -$bytes, $bytes) === 0;
    }

    private function contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }

    private function stripPrefix(string $prefixedText, string $prefix): string
    {
        if (strpos($prefixedText, $prefix) !== 0) {
            return $prefixedText;
        }

        return substr($prefixedText, strlen($prefix));
    }

    private function stripSuffix(string $text, string $suffix): string
    {
        if (!$this->endsWith($text, $suffix)) {
            return $text;
        }

        return substr($text, 0, -strlen($suffix));
    }
}