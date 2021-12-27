<?php

namespace Coil\PhpDecorator\Tests\TestClasses;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Cache extends \Coil\PhpDecorator\Decorator
{
    private mixed $result;
    private bool $stored = false;

    public function wrap(callable $func): callable
    {
        return function () use ($func) {

            if ($this->stored) {
                return $this->result;
            }

            $this->result = call_user_func_array($func, func_get_args());
            $this->stored = true;

            return $this->result;
        };
    }
}
