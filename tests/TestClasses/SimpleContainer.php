<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

use Psr\Container\ContainerInterface;

class SimpleContainer implements ContainerInterface
{
    private array $objs = [];

    public function get(string $id)
    {
        return $this->objs[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->objs[$id]);
    }

    public function set(string $id, mixed $obj): void
    {
        $this->objs[$id] = $obj;
    }
}
