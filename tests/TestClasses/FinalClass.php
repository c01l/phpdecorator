<?php

namespace Coil\PhpDecorator\Tests\TestClasses;

use Coil\PhpDecorator\Decorator;

final class FinalClass
{

    #[Decorator("bla")]
    public function testFunc()
    {
    }

}