<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

abstract class AbstractServiceClass
{
    abstract public function process(): void;
}
