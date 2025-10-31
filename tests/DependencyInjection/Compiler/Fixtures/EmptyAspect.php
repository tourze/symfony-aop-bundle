<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class EmptyAspect
{
    #[Before(statement: 'method.getName() == "nonExistentMethod"')]
    public function beforeNonExistent(JoinPoint $joinPoint): void
    {
        // This will never match
    }
}
