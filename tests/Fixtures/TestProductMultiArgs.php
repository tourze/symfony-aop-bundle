<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class TestProductMultiArgs
{
    public function __construct(
        public array $args
    ) {}
}