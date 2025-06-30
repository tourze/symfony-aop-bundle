<?php

namespace Tourze\Symfony\Aop\Service;

use Tourze\Symfony\Aop\Model\JoinPoint;

class InstanceService
{
    public function create(JoinPoint $joinPoint): object
    {
        $instance = $joinPoint->getInstance();
        $factoryInstance = $joinPoint->getFactoryInstance();
        $factoryMethod = $joinPoint->getFactoryMethod();
        $factoryArguments = $joinPoint->getFactoryArguments();

        if (is_string($factoryInstance)) {
            /** @phpstan-ignore-next-line */
            return $factoryInstance::{$factoryMethod}(...$factoryArguments);
            //return call_user_func_array([$factoryInstance, $factoryMethod], $factoryArguments);
        }
        if (is_object($factoryInstance)) {
            /** @phpstan-ignore-next-line */
            return $factoryInstance->{$factoryMethod}(...$factoryArguments);
        }
        return clone $instance;
    }
}
