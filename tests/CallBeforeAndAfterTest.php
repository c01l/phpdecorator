<?php

namespace C01l\PhpDecorator\Tests;

use C01l\PhpDecorator\DecoratorManager;
use C01l\PhpDecorator\Tests\TestClasses\CounterUserClass;
use C01l\PhpDecorator\Tests\TestClasses\CounterUtil;
use PHPUnit\Framework\TestCase;

class CallBeforeAndAfterTest extends TestCase
{
    private DecoratorManager $sut;

    protected function setUp(): void
    {
        $this->sut = new DecoratorManager();
        CounterUtil::reset();
    }

    public function testCallBefore()
    {
        $cu = new CounterUserClass();
        $cu = $this->sut->decorate($cu);
        $this->assertEquals(0, CounterUtil::getCount());
        $ret = $cu->countBefore();
        $this->assertEquals(1, $ret);
        $this->assertEquals(1, CounterUtil::getCount());
    }

    public function testCallAfter()
    {
        $cu = new CounterUserClass();
        $cu = $this->sut->decorate($cu);
        $this->assertEquals(0, CounterUtil::getCount());
        $ret = $cu->countAfter();
        $this->assertEquals(0, $ret);
        $this->assertEquals(1, CounterUtil::getCount());
    }

    public function testCallBeforeInstantiated()
    {
        $cu = $this->sut->instantiate(CounterUserClass::class);
        $this->assertEquals(0, CounterUtil::getCount());
        $ret = $cu->countBefore();
        $this->assertEquals(1, $ret);
        $this->assertEquals(1, CounterUtil::getCount());
    }

    public function testCallAfterInstantiated()
    {
        $cu = $this->sut->instantiate(CounterUserClass::class);
        $this->assertEquals(0, CounterUtil::getCount());
        $ret = $cu->countAfter();
        $this->assertEquals(0, $ret);
        $this->assertEquals(1, CounterUtil::getCount());
    }
}
