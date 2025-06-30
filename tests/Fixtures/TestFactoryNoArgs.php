<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class TestFactoryNoArgs
{
    public static function create(): TestProductNoArgs
    {
        return new TestProductNoArgs();
    }
}