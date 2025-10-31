<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class AspectClassForParents
{
    #[Before(statement: 'in_array("Tourze\\\Symfony\\\Aop\\\Tests\\\DependencyInjection\\\Compiler\\\Fixtures\\\AbstractServiceClass", parentClasses) and method.getName() == "process"')]
    public function beforeParentClass(JoinPoint $joinPoint): void
    {
        // Before advice for parent class
    }
}
