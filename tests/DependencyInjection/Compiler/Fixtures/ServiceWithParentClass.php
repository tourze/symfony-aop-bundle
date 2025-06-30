<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

/** @phpstan-ignore-next-line */
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