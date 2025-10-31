<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

class ServiceFactory
{
    public function createService(string $arg1, string $arg2): ServiceWithFactoryClass
    {
        return new ServiceWithFactoryClass($arg1, $arg2);
    }
}
