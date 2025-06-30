<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class TestFactory
{
    public static function createStaticInstance(string $arg1, int $arg2): TestProduct
    {
        return new TestProduct($arg1, $arg2);
    }
    
    public function createInstance(string $arg1, int $arg2): TestProduct
    {
        return new TestProduct($arg1, $arg2);
    }
}