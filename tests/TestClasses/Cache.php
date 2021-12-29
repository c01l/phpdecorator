<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Cache extends \C01l\PhpDecorator\Decorator
{
    private mixed $result;
    private bool $stored = false;

    public function wrap(callable $func): callable
    {
        return function (...$args) use ($func) {

            if ($this->stored) {
                return $this->result;
            }

            $this->result = $func(...$args);
            $this->stored = true;

            return $this->result;
        };
    }
}
