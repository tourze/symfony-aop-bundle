<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class ContainerTestAspect
{
    public array $log = [];
    
    #[Before(statement: 'method.getName() == "doWork"')]
    public function beforeWork(JoinPoint $joinPoint): void
    {
        $this->log[] = 'before';
    }
    
    #[After(statement: 'method.getName() == "doWork"')]
    public function afterWork(JoinPoint $joinPoint): void
    {
        $this->log[] = 'after';
    }
    
    #[AfterReturning(statement: 'method.getName() == "doWork"')]
    public function afterReturningWork(JoinPoint $joinPoint): void
    {
        $this->log[] = 'afterReturning';
    }
    
    #[AfterThrowing(statement: 'method.getName() == "throwError"')]
    public function afterThrowingError(JoinPoint $joinPoint): void
    {
        $this->log[] = 'afterThrowing';
        // Note: Currently AopInterceptor doesn't support modifying exceptions
    }
}