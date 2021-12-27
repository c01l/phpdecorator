<?php

namespace Coil\PhpDecorator\Tests\TestClasses;

class Logger
{
    private array $log = [];

    public function log(string $msg): void
    {
        $this->log[] = $msg;
        echo "Logger: " . $msg, PHP_EOL;
    }

    /**
     * @return string[]
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
