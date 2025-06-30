<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

/** @phpstan-ignore-next-line */
class ServiceWithInterface implements ServiceInterface
{
    public function execute(): void
    {
        // Implementation
    }
}