<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class AspectClassForInterfaces
{
    #[Before(statement: 'in_array("Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\DependencyInjection\\\\Compiler\\\\Fixtures\\\\ServiceInterface", parentClasses) and method.getName() == "execute"')]
    public function beforeInterface(JoinPoint $joinPoint): void
    {
        // Before advice for interface
    }
}