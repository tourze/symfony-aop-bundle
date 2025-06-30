<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class TestProduct
{
    public function __construct(
        public string $arg1,
        public int $arg2
    ) {}
}