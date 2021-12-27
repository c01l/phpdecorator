<?php

namespace Coil\PhpDecorator\Tests\TestClasses;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LoggingDecorator extends \Coil\PhpDecorator\Decorator
{
    public function wrap(callable $func): callable
    {
        return function () use ($func) {
            /** @var Logger $logger */
            $logger = $this->getContainer()->get(Logger::class);
            $logger->log("Started");
            $ret = call_user_func_array($func, func_get_args());
            $logger->log("Ended");
            return $ret;
        };
    }
}
