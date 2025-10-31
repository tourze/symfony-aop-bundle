<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

/**
 * 测试用的服务类
 */
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
