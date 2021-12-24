<?php

namespace Coil\PhpDecorator;

use Exception;
use Throwable;

class DecoratorException extends Exception
{
    public function __construct($message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}