<?php

namespace App\Services;

class LogService
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: __DIR__.'/../../storage/logs';
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    public function append(string $filename, string $message): void
    {
        $path = $this->basePath.'/'.$filename;
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($path, $line, FILE_APPEND);
    }

    public function tail(string $filename, int $limitBytes = 4000): string
    {
        $path = $this->basePath.'/'.$filename;
        if (!file_exists($path)) {
            return "Log file not found";
        }
        $size = filesize($path);
        $offset = max($size - $limitBytes, 0);
        $fh = fopen($path, 'r');
        fseek($fh, $offset);
        $content = stream_get_contents($fh);
        fclose($fh);
        return $content;
    }
}
