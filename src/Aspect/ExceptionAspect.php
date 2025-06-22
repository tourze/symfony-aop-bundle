<?php

namespace Tourze\Symfony\Aop\Aspect;

use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\CatchException;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class ExceptionAspect
{
    #[AfterThrowing(classAttribute: CatchException::class)]
    public function catchException(JoinPoint $joinPoint): void
    {
        if ($joinPoint->getException() !== null) {
            //dump($joinPoint->getException());
        }
    }
}
