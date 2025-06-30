<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

/** @phpstan-ignore-next-line */
class ServiceFactory
{
    public function createService(string $arg1, string $arg2): ServiceWithFactoryClass
    {
        return new ServiceWithFactoryClass($arg1, $arg2);
    }
}