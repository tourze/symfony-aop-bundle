<?php

namespace Tourze\Symfony\AOP\Aspect;

use Tourze\Symfony\AOP\Attribute\AfterThrowing;
use Tourze\Symfony\AOP\Attribute\Aspect;
use Tourze\Symfony\AOP\Attribute\CatchException;
use Tourze\Symfony\AOP\Model\JoinPoint;

#[Aspect]
class ExceptionAspect
{
    #[AfterThrowing(classAttribute: CatchException::class)]
    public function catchException(JoinPoint $joinPoint): void
    {
        if ($joinPoint->getException()) {
            //dump($joinPoint->getException());
        }
    }
}
