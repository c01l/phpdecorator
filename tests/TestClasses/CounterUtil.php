<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

class CounterUtil
{
    private static int $counter = 0;

    public static function increment(): void
    {
        self::$counter++;
    }

    public static function reset(): void
    {
        self::$counter = 0;
    }

    public static function getCount(): int
    {
        return self::$counter;
    }
}
