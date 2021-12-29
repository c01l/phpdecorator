<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

use C01l\PhpDecorator\Decorator;

final class FinalClass
{
    #[Decorator("bla")]
    public function testFunc()
    {
    }
}
