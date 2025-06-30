<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class TestFactoryMultiArgs
{
    public static function create(...$args): TestProductMultiArgs
    {
        return new TestProductMultiArgs($args);
    }
}