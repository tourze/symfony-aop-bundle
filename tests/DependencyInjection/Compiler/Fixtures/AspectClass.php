<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class AspectClass
{
    #[Before(statement: 'method.getName() == "doSomething"')]
    public function beforeMethod(JoinPoint $joinPoint): void
    {
        // Before advice logic
    }

    #[After(statement: '"monitored" in serviceTags')]
    public function afterTaggedServices(JoinPoint $joinPoint): void
    {
        // After advice for tagged services
    }

    #[Before(statement: 'in_array("Tourze\\\Symfony\\\Aop\\\Tests\\\DependencyInjection\\\Compiler\\\Fixtures\\\AbstractServiceClass", parentClasses)')]
    public function beforeParentClass(JoinPoint $joinPoint): void
    {
        // Before advice for parent class
    }

    #[Before(statement: 'in_array("Tourze\\\Symfony\\\Aop\\\Tests\\\DependencyInjection\\\Compiler\\\Fixtures\\\ServiceInterface", parentClasses)')]
    public function beforeInterface(JoinPoint $joinPoint): void
    {
        // Before advice for interface
    }

    #[Before(statement: 'method.getName() == "process"')]
    public function beforeProcess(JoinPoint $joinPoint): void
    {
        // Before advice for process method
    }
}
