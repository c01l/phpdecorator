<?php

namespace C01l\PhpDecorator\Tests\TestClasses;

class SimpleMethodsClass
{
    #[LoggingDecorator]
    public function superTest(int $bla, string $doo, float $fllloooat): int
    {
        return $bla;
    }

    #[LoggingDecorator]
    public function superDefaultsTest(int $bla = 1, string $doo = "asdfg", float $fllloooat = 0.0): int
    {
        return $bla;
    }

    public function undecoratedFunc(): string
    {
        return "hi";
    }
}
