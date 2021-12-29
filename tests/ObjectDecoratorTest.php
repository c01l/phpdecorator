<?php

namespace C01l\PhpDecorator\Tests;

use C01l\PhpDecorator\DecoratorException;
use C01l\PhpDecorator\DecoratorManager;
use C01l\PhpDecorator\Tests\TestClasses\FinalClass;
use C01l\PhpDecorator\Tests\TestClasses\Logger;
use C01l\PhpDecorator\Tests\TestClasses\MultipleDecoratorClass;
use C01l\PhpDecorator\Tests\TestClasses\SimpleContainer;
use C01l\PhpDecorator\Tests\TestClasses\SimpleMethodsClass;
use C01l\PhpDecorator\Tests\TestClasses\UnrelatedAttribute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ObjectDecoratorTest extends TestCase
{
    private DecoratorManager $sut;
    private SimpleContainer $simpleContainer;
    private string $testClassCache;

    protected function setUp(): void
    {
        $this->testClassCache = sys_get_temp_dir() . "/decoratorTestCache/";
        @mkdir($this->testClassCache, recursive: true);

        array_map('unlink', array_filter((array)glob($this->testClassCache . "*") ?: []));

        $this->simpleContainer = new SimpleContainer();
        $this->sut = new DecoratorManager($this->testClassCache, $this->simpleContainer);

        $logger = new Logger();
        $this->simpleContainer->set(Logger::class, $logger);
    }

    protected function tearDown(): void
    {
        @rmdir($this->testClassCache);
    }

    public function testCanDecorateSimpleFunction()
    {
        $smc = new SimpleMethodsClass();
        $obj = $this->sut->decorate($smc);
        $this->assertEquals(1, $obj->superTest(1, "sadfg", 2.4));
        $this->assertEquals(2, $obj->superDefaultsTest(2));
        $this->assertEquals(1, $obj->superDefaultsTest());
        $this->assertEquals("hi", $obj->undecoratedFunc());
        $this->assertCount(6, $this->simpleContainer->get(Logger::class)->getLog());
    }

    public function testFinalClassCannotBeDecorated()
    {
        $this->expectException(DecoratorException::class);
        $fc = new FinalClass();
        $this->sut->decorate($fc);
    }

    public function testMultipleDecorators()
    {
        /** @var MultipleDecoratorClass $obj */
        $mdc = new MultipleDecoratorClass();
        $obj = $this->sut->decorate($mdc);
        $this->assertEquals(123, $obj->superDefaultsTest(123));
        $this->assertEquals(123, $obj->superDefaultsTest(456));
        $this->assertCount(4, $this->simpleContainer->get(Logger::class)->getLog());
    }

    public function testKeepsUnrelatedAttributes()
    {
        $mdc = new MultipleDecoratorClass();
        $obj = $this->sut->decorate($mdc);
        $this->assertNotNull($obj);
        $rc = new ReflectionClass($obj);
        $m = $rc->getMethod("superDefaultsTest");
        $attrs = $m->getAttributes();
        $attrNames = array_map(fn($a) => $a->getName(), $attrs);
        $this->assertEquals([UnrelatedAttribute::class], $attrNames);
    }
}
