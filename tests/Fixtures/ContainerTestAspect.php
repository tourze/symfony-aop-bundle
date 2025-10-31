<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;

/**
 * 测试用的切面类
 */
#[Aspect]
class ContainerTestAspect
{
    public array $log = [];

    #[Before(statement: 'class.getName() == "Tourze\\\Symfony\\\Aop\\\Tests\\\Fixtures\\\ContainerTestService" and method.getName() == "doWork"')]
    public function beforeAdvice(): void
    {
        $this->log[] = 'before';
    }

    #[AfterReturning(statement: 'class.getName() == "Tourze\\\Symfony\\\Aop\\\Tests\\\Fixtures\\\ContainerTestService" and method.getName() == "doWork"')]
    public function afterReturningAdvice(): void
    {
        $this->log[] = 'afterReturning';
    }

    #[After(statement: 'class.getName() == "Tourze\\\Symfony\\\Aop\\\Tests\\\Fixtures\\\ContainerTestService" and method.getName() == "doWork"')]
    public function afterAdvice(): void
    {
        $this->log[] = 'after';
    }

    #[AfterThrowing(statement: 'class.getName() == "Tourze\\\Symfony\\\Aop\\\Tests\\\Fixtures\\\ContainerTestService" and method.getName() == "throwError"')]
    public function afterThrowingAdvice(): void
    {
        $this->log[] = 'afterThrowing';
    }
}
