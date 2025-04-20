<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

/**
 * 测试用的切面类
 */
#[Aspect]
class TestAspect
{
    private array $log = [];

    public function getLog(): array
    {
        return $this->log;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    private function addLog(string $message): void
    {
        $this->log[] = $message;
    }

    #[Before('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "normalMethod"')]
    public function beforeNormalMethod(JoinPoint $joinPoint): void
    {
        $params = $joinPoint->getParams();
        $param1 = $params['param1'] ?? 'unknown';
        $param2 = $params['param2'] ?? 0;

        $this->addLog("Before: normalMethod with $param1 and $param2");
    }

    #[After('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "normalMethod"')]
    public function afterNormalMethod(JoinPoint $joinPoint): void
    {
        $this->addLog("After: normalMethod");
    }

    #[AfterReturning('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "normalMethod"')]
    public function afterReturningNormalMethod(JoinPoint $joinPoint): void
    {
        $returnValue = $joinPoint->getReturnValue();
        $this->addLog("AfterReturning: normalMethod returned $returnValue");
    }

    #[Before('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "exceptionMethod"')]
    public function beforeExceptionMethod(JoinPoint $joinPoint): void
    {
        $params = $joinPoint->getParams();
        $message = $params['message'] ?? 'unknown';

        $this->addLog("Before: exceptionMethod with message: $message");
    }

    #[After('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "exceptionMethod"')]
    public function afterExceptionMethod(JoinPoint $joinPoint): void
    {
        $this->addLog("After: exceptionMethod");
    }

    #[AfterThrowing('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "exceptionMethod"')]
    public function afterThrowingExceptionMethod(JoinPoint $joinPoint): void
    {
        $exception = $joinPoint->getException();
        $message = $exception ? $exception->getMessage() : 'unknown';

        $this->addLog("AfterThrowing: exceptionMethod threw exception with message: $message");

        // 可以设置提前返回，防止异常继续传播
        // $joinPoint->setReturnEarly(true);
        // $joinPoint->setReturnValue(null);
    }
}
