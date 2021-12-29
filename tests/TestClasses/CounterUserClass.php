<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

use C01l\PhpDecorator\CallAfter;
use C01l\PhpDecorator\CallBefore;

class CounterUserClass
{
    #[CallBefore([CounterUtil::class, "increment"])]
    public function countBefore(): int
    {
        return CounterUtil::getCount();
    }

    #[CallAfter([CounterUtil::class, "increment"])]
    public function countAfter(): int
    {
        return CounterUtil::getCount();
    }
}
