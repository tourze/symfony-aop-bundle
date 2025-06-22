<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

class ServiceWithParentClass extends AbstractServiceClass implements ServiceInterface
{
    public function process(): void
    {
        // Implementation
    }

    public function execute(): void
    {
        // Implementation
    }
}