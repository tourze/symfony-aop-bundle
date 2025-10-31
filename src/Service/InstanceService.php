<?php

namespace Tourze\Symfony\Aop\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\Symfony\Aop\Exception\AopException;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Autoconfigure(public: true)]
class InstanceService
{
    public function create(JoinPoint $joinPoint): object
    {
        $instance = $joinPoint->getInstance();
        $factoryInstance = $joinPoint->getFactoryInstance();
        $factoryMethod = $joinPoint->getFactoryMethod();
        $factoryArguments = $joinPoint->getFactoryArguments();

        if (is_string($factoryInstance) && null !== $factoryMethod && null !== $factoryArguments) {
            $callable = [$factoryInstance, $factoryMethod];
            if (!is_callable($callable)) {
                throw new AopException(sprintf('Static method %s::%s is not callable', $factoryInstance, $factoryMethod));
            }

            return \call_user_func($callable, ...$factoryArguments);
        }
        if (is_object($factoryInstance) && null !== $factoryMethod && null !== $factoryArguments) {
            $callable = [$factoryInstance, $factoryMethod];
            if (!is_callable($callable)) {
                throw new AopException(sprintf('Method %s::%s is not callable', get_class($factoryInstance), $factoryMethod));
            }

            return \call_user_func($callable, ...$factoryArguments);
        }

        return clone $instance;
    }
}
