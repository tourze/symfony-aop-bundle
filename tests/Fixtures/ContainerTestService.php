<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

class ContainerTestService
{
    public function doWork(): string
    {
        return 'original';
    }
    
    public function throwError(): void
    {
        throw new TestRuntimeException('Test error');
    }
}