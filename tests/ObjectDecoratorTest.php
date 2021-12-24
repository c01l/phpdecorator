<?php

namespace Coil\PhpDecorator\Tests;

use Coil\PhpDecorator\DecoratorException;
use Coil\PhpDecorator\ObjectDecorator;
use Coil\PhpDecorator\Tests\TestClasses\FinalClass;
use Coil\PhpDecorator\Tests\TestClasses\Logger;
use Coil\PhpDecorator\Tests\TestClasses\MultipleDecoratorClass;
use Coil\PhpDecorator\Tests\TestClasses\SimpleContainer;
use Coil\PhpDecorator\Tests\TestClasses\SimpleMethodsClass;
use PHPUnit\Framework\TestCase;

class ObjectDecoratorTest extends TestCase
{

    private ObjectDecorator $sut;
    private SimpleContainer $simpleContainer;

    protected function setUp(): void
    {
        $this->simpleContainer = new SimpleContainer();
        $this->sut = new ObjectDecorator($this->simpleContainer);
        $logger = new Logger();
        $this->simpleContainer->set(Logger::class, $logger);
    }


    public function test_canDecorateSimpleFunction()
    {
        $smc = new SimpleMethodsClass();
        /** @var SimpleMethodsClass $obj */
        $obj = $this->sut->decorate($smc);
        $this->assertEquals(1, $obj->superTest(1, "sadfg", 2.4));
        $this->assertEquals(2, $obj->superDefaultsTest(2));
        $this->assertEquals(1, $obj->superDefaultsTest());
        $this->assertEquals("hi", $obj->undecoratedFunc());
        $this->assertCount(6, $this->simpleContainer->get(Logger::class)->getLog());
    }

    public function test_finalClass_cannotBeDecorated() {
        $this->expectException(DecoratorException::class);
        $fc = new FinalClass();
        $this->sut->decorate($fc);
    }

    public function test_multipleDecorators() {
        /** @var MultipleDecoratorClass $obj */
        $mdc = new MultipleDecoratorClass();
        $obj = $this->sut->decorate($mdc);
        $this->assertEquals(123, $obj->superDefaultsTest(123));
        $this->assertEquals(123, $obj->superDefaultsTest(456));
        $this->assertCount(4, $this->simpleContainer->get(Logger::class)->getLog());
    }

}