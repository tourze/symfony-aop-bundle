<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

/** @phpstan-ignore-next-line */
#[Aspect]
class AspectWithMultipleAdvices
{
    #[Before(statement: 'method.getName() == "doSomething"')]
    public function beforeAdvice(JoinPoint $joinPoint): void
    {
        // Before logic
    }

    #[After(statement: 'method.getName() == "doSomething"')]
    public function afterAdvice(JoinPoint $joinPoint): void
    {
        // After logic
    }

    #[AfterReturning(statement: 'method.getName() == "doSomething"')]
    public function afterReturningAdvice(JoinPoint $joinPoint): void
    {
        // After returning logic
    }

    #[AfterThrowing(statement: 'method.getName() == "doSomething"')]
    public function afterThrowingAdvice(JoinPoint $joinPoint): void
    {
        // After throwing logic
    }
}