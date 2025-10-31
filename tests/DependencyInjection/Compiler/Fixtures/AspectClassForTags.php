<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class AspectClassForTags
{
    #[After(statement: '"monitored" in serviceTags')]
    public function afterTaggedServices(JoinPoint $joinPoint): void
    {
        // After advice for tagged services only
    }
}
