<?php

namespace Coil\PhpDecorator\Tests\TestClasses;

class MultipleDecoratorClass
{
    #[LoggingDecorator]
    #[Cache]
    #[UnrelatedAttribute]
    public function superDefaultsTest(int $bla = 1, string $doo = "asdfg", float $fllloooat = 0.0): int
    {
        return $bla;
    }

    public function undecoratedFunc(): string
    {
        return "hi";
    }
}
