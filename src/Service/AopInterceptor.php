<?php

namespace Tourze\Symfony\Aop\Service;

use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Model\PassTrait;

class AopInterceptor
{
    use PassTrait;

    public static int $globalSequenceId = 0;

    private array $attributes = [];

    public function addAttributeFunction(string $interceptMethod, string $attribute, \Closure $aspectService, string $aspectMethod): void
    {
        if (!isset($this->attributes[$interceptMethod])) {
            $this->attributes[$interceptMethod] = [];
        }
        if (!isset($this->attributes[$interceptMethod][$attribute])) {
            $this->attributes[$interceptMethod][$attribute] = [];
        }
        $this->attributes[$interceptMethod][$attribute][] = [$aspectService, $aspectMethod];
    }

    private function createJoinPoint(object $proxy, object $instance, string $method, array $params): JoinPoint
    {
        // 根据入參，我们构造一个JoinPoint
        $joinPoint = new JoinPoint();
        $joinPoint->setProxy($proxy);
        $joinPoint->setInstance($instance);
        $joinPoint->setMethod($method);
        $joinPoint->setParams($params);

        // 传递一些编译器用到的参数下去
        $joinPoint->setInternalServiceId($this->getInternalServiceId());
        $joinPoint->setProxyServiceId($this->getProxyServiceId());
        $joinPoint->setFactoryInstance($this->getFactoryInstance());
        $joinPoint->setFactoryMethod($this->getFactoryMethod());
        $joinPoint->setFactoryArguments($this->getFactoryArguments());

        // 标记执行时序，方便我们在下面识别
        static::$globalSequenceId++;
        $joinPoint->setSequenceId(static::$globalSequenceId);

        return $joinPoint;
    }

    const REDIS_FIX_METHODS = [
        'acl',
        'bitop',
        'blpop',
        'brpop',
        'bzpopmax',
        'bzpopmin',
        'client',
        'command',
        'del',
        'exists',
        'geoadd',
        'geohash',
        'geopos',
        'hdel',
        'pubsub',
        'punsubscribe',
        'rawcommand',
        'sdiff',
        'sdiffstore',
        'sinter',
        'sinterstore',
        'smismember',
        'sunion',
        'sunionstore',
        'script',
        'srem',
        'unlink',
        'unsubscribe',
        'watch',
        'zadd',
        'zrem',
        'delete',
        'sremove',
        'zdelete',
        'zremove',
        'lpush',
    ];

    /**
     * 任何类型的注解，最终我们都是丢到这里去处理
     *
     * @param object $proxy the proxy that intercepted the method call
     * @param object $instance the wrapped instance within the proxy
     * @param string $method name of the called method
     * @param array $params sorted array of parameters passed to the intercepted
     *                          method, indexed by parameter name
     * @param bool $returnEarly flag to tell the interceptor proxy to return early, returning
     *                          the interceptor's return value instead of executing the method logic
     * @return mixed
     */
    public function __invoke(object $proxy, object $instance, string $method, array $params, bool &$returnEarly): mixed
    {
        // 如果没设置拦截，则直接返回
        if (!isset($this->attributes[$method])) {
            return null;
        }

        // 如果一个方法需要被AOP拦截，那么无论如何结果我们都需要拦截的了
        $returnEarly = true;

        $joinPoint = $this->createJoinPoint($proxy, $instance, $method, $params);

        // 执行Before
        foreach ($this->attributes[$method][Before::class] ?? [] as [$aspectService, $aspectMethod]) {
            call_user_func_array([$aspectService(), $aspectMethod], [$joinPoint]);
            if ($joinPoint->isReturnEarly()) {
                return $joinPoint->getReturnValue();
            }
            // 在 Before 阶段，我们可能会主动替换 instance 对象，以实现更加复杂的实例逻辑
            $instance = $joinPoint->getInstance();
        }

        $returnValue = null;
        try {
            // 执行目标方法

            // FIX Redis有一些方法，需要特殊处理一次
            if ($instance instanceof \Redis) {
                if ($method === 'info' && isset($params['sections'])) {
                    $params = $params['sections'];
                }

                // 有相当一部分函数需要兼容，他们最后一参数是可选的，... $args 这样子定义的
                if (in_array(strtolower($method), static::REDIS_FIX_METHODS) && is_array(end($params))) {
                    $extraParams = array_pop($params);
                    if ($extraParams) {
                        $params = array_merge($params, $extraParams);
                        $params = array_values($params);
                    }
                }
            }

            $returnValue = $instance->{$method}(...$params);
            $joinPoint->setReturnValue($returnValue);

            try {
                return $returnValue;
            } finally {
                // @AfterReturning：如果目标方法成功执行（即没有抛出异常），则执行返回通知。
                foreach ($this->attributes[$method][AfterReturning::class] ?? [] as [$aspectService, $aspectMethod]) {
                    call_user_func_array([$aspectService(), $aspectMethod], [$joinPoint]);
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
            $joinPoint->setException($exception);
            foreach ($this->attributes[$method][AfterThrowing::class] ?? [] as [$aspectService, $aspectMethod]) {
                call_user_func_array([$aspectService(), $aspectMethod], [$joinPoint]);
                if ($joinPoint->isReturnEarly()) {
                    return $joinPoint->getReturnValue();
                }
            }
            throw $exception;
        } finally {
            // @After：最后，无论目标方法是否成功执行或抛出异常，都会执行后置通知。这是通知执行的最后阶段，通常用于执行一些清理工作、日志记录等。
            foreach ($this->attributes[$method][After::class] ?? [] as [$aspectService, $aspectMethod]) {
                call_user_func_array([$aspectService(), $aspectMethod], [$joinPoint]);
                // TODO 这个阶段是否应该还会修改返回值？
                /*
                if ($joinPoint->isReturnEarly()) {
                    return $joinPoint->getReturnValue();
                }
                */
            }
        }
    }
}
