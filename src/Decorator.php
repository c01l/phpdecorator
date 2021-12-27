<?php

namespace Coil\PhpDecorator;

use Attribute;
use Psr\Container\ContainerInterface;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
abstract class Decorator
{
    private ContainerInterface $container;

    abstract public function wrap(callable $func): callable;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
