<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

class ServiceWithInterface implements ServiceInterface
{
    public function execute(): void
    {
        // Implementation
    }
}