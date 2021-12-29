<?php

namespace C01l\PhpDecorator;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class CallAfter extends Decorator
{
    public function __construct(private string|array $func)
    {
    }

    public function wrap(callable $func): callable
    {
        return function () use ($func) {
            $v = $func(...func_get_args());
            ($this->func)();
            return $v;
        };
    }
}
