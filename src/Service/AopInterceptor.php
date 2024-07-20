<?php

namespace AopBundle\Service;

use AopBundle\Attribute\After;
use AopBundle\Attribute\AfterReturning;
use AopBundle\Attribute\AfterThrowing;
use AopBundle\Attribute\Before;
use AopBundle\Model\JoinPoint;
use Psr\Container\ContainerInterface;

class AopInterceptor
{
    public static int $globalSequenceId = 0;

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    private ?string $serviceId = null;

    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): void
    {
        $this->serviceId = $serviceId;
    }

    private ?string $method = null;

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    private array $attributes = [];

    public function addAttributes(array $config): void
    {
        foreach ($config as $attribute => $value) {
            if (!isset($this->attributes[$attribute])) {
                $this->attributes[$attribute] = [];
            }
            $this->attributes[$attribute] = array_merge($this->attributes[$attribute], $value);
        }
    }

    /**
     * 任何类型的注解，最终我们都是丢到这里去处理
     *
     * @return mixed
     * @var object $proxy the proxy that intercepted the method call
     * @var object $instance the wrapped instance within the proxy
     * @var string $method name of the called method
     * @var array $params sorted array of parameters passed to the intercepted
     *                          method, indexed by parameter name
     * @var bool $returnEarly flag to tell the interceptor proxy to return early, returning
     *                          the interceptor's return value instead of executing the method logic
     */
    public function __invoke(object $proxy, object $instance, string $method, array $params, &$returnEarly): mixed
    {
        // 如果一个方法需要被AOP拦截，那么无论如何结果我们都需要拦截的了
        $returnEarly = true;

        // 根据入參，我们构造一个JoinPoint
        $joinPoint = new JoinPoint();
        $joinPoint->setProxy($proxy);
        $joinPoint->setInstance($instance);
        $joinPoint->setMethod($method);
        $joinPoint->setParams($params);
        $joinPoint->setServiceId($this->getServiceId());

        // 标记执行时序，方便我们在下面识别
        static::$globalSequenceId++;
        $joinPoint->setSequenceId(static::$globalSequenceId);

        // 执行Before
        foreach ($this->attributes[Before::class] ?? [] as [$serviceId, $func]) {
            $_service = $this->container->get($serviceId);
            call_user_func_array([$_service, $func], [$joinPoint]);
            if ($joinPoint->isReturnEarly()) {
                return $joinPoint->getReturnValue();
            }
        }

        try {
            // 执行目标方法
            $returnValue = call_user_func_array([$instance, $method], $params);
            $joinPoint->setReturnValue($returnValue);

            try {
                return $returnValue;
            } finally {
                // @AfterReturning：如果目标方法成功执行（即没有抛出异常），则执行返回通知。
                foreach ($this->attributes[AfterReturning::class] ?? [] as [$serviceId, $func]) {
                    $_service = $this->container->get($serviceId);
                    call_user_func_array([$_service, $func], [$joinPoint]);
                    // TODO 这个阶段是否应该还会修改返回值？
                    /*
                    if ($joinPoint->isReturnEarly()) {
                        return $joinPoint->getReturnValue();
                    }
                    */
                }
            }
        } catch (\Throwable $exception) {
            // @AfterThrowing：如果目标方法抛出异常，则执行异常通知。注意，如果目标方法成功执行，则不会执行此通知。
            foreach ($this->attributes[AfterThrowing::class] ?? [] as [$serviceId, $func]) {
                $_service = $this->container->get($serviceId);
                call_user_func_array([$_service, $func], [$joinPoint]);
                if ($joinPoint->isReturnEarly()) {
                    return $joinPoint->getReturnValue();
                }
            }
            throw $exception;
        } finally {
            // @After：最后，无论目标方法是否成功执行或抛出异常，都会执行后置通知。这是通知执行的最后阶段，通常用于执行一些清理工作、日志记录等。
            foreach ($this->attributes[After::class] ?? [] as [$serviceId, $func]) {
                $_service = $this->container->get($serviceId);
                call_user_func_array([$_service, $func], [$joinPoint]);
                // TODO 这个阶段是否应该还会修改返回值？
                /*
                if ($joinPoint->isReturnEarly()) {
                    return $joinPoint->getReturnValue();
                }
                */
            }
        }

        return $returnValue;
    }
}
