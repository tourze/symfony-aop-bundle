<?php

namespace Tourze\Symfony\AOP\Service;

use Tourze\Symfony\AOP\Model\JoinPoint;

class InstanceService
{
    public function create(JoinPoint $joinPoint): object
    {
        $instance = $joinPoint->getInstance();
        $factoryInstance = $joinPoint->getFactoryInstance();
        $factoryMethod = $joinPoint->getFactoryMethod();
        $factoryArguments = $joinPoint->getFactoryArguments();

        if (is_string($factoryInstance)) {
            return $factoryInstance::{$factoryMethod}(...$factoryArguments);
            //return call_user_func_array([$factoryInstance, $factoryMethod], $factoryArguments);
        }
        if (is_object($factoryInstance)) {
            return $factoryInstance->{$factoryMethod}(...$factoryArguments);
        }
        return clone $instance;
    }
}
