<?php

namespace Tourze\Symfony\Aop\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Around;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Exception\AopException;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Model\PassTrait;

#[Autoconfigure(public: true)]
class AopInterceptor
{
    use PassTrait;

    public static int $globalSequenceId = 0;

    /**
     * @var array<string, array<string, array<array{\Closure, string}>>>
     */
    private array $attributes = [];

    private ?JoinPointPool $joinPointPool = null;

    public function setJoinPointPool(JoinPointPool $pool): void
    {
        $this->joinPointPool = $pool;
    }

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

    /**
     * @param array<mixed> $params
     */
    private function createJoinPoint(object $proxy, object $instance, string $method, array $params): JoinPoint
    {
        // 使用对象池获取JoinPoint
        $joinPoint = null !== $this->joinPointPool ? $this->joinPointPool->acquire() : new JoinPoint();
        $joinPoint->setProxy($proxy);
        $joinPoint->setInstance($instance);
        $joinPoint->setMethod($method);
        $joinPoint->setParams($params);

        // 传递一些编译器用到的参数下去
        $internalServiceId = $this->getInternalServiceId();
        if (null !== $internalServiceId) {
            $joinPoint->setInternalServiceId($internalServiceId);
        }
        $joinPoint->setProxyServiceId($this->getProxyServiceId());
        $joinPoint->setFactoryInstance($this->getFactoryInstance());
        $joinPoint->setFactoryMethod($this->getFactoryMethod());
        $joinPoint->setFactoryArguments($this->getFactoryArguments());

        // 标记执行时序，方便我们在下面识别
        ++static::$globalSequenceId;
        $joinPoint->setSequenceId(static::$globalSequenceId);

        return $joinPoint;
    }

    /**
     * @var array<string>
     */
    private array $redisFixMethods = [
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
     * @param object $proxy 拦截方法调用的代理对象
     * @param object $instance 代理中包装的实例
     * @param string $method 被调用方法的名称
     * @param array<mixed> $params 传递给被拦截方法的参数数组，
     *                            按参数名称索引
     * @param bool $returnEarly 标志位，用于告诉拦截器代理提前返回，
     *                            返回拦截器的返回值而不是执行方法逻辑
     * @return mixed
     * @throws \Throwable
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

        try {
            // 检查是否有Around通知
            if (isset($this->attributes[$method][Around::class])) {
                return $this->executeAroundAdvice($joinPoint, $method, $instance, $params);
            }

            // 执行Before
            $instance = $this->executeBeforeAdvices($joinPoint, $method, $instance);
            if ($joinPoint->isReturnEarly()) {
                return $joinPoint->getReturnValue();
            }

            try {
                $returnValue = $this->executeTargetMethod($instance, $method, $params, $joinPoint);
                $this->executeAfterReturningAdvices($joinPoint, $method);

                return $returnValue;
            } catch (\Throwable $exception) {
                $earlyReturn = $this->executeAfterThrowingAdvices($joinPoint, $method, $exception);
                if (null !== $earlyReturn) {
                    return $earlyReturn;
                }
                throw $exception;
            } finally {
                $this->executeAfterAdvices($joinPoint, $method);
            }
        } finally {
            // 释放JoinPoint回对象池
            if (null !== $this->joinPointPool) {
                $this->joinPointPool->release($joinPoint);
            }
        }
    }

    private function executeBeforeAdvices(JoinPoint $joinPoint, string $method, object $instance): object
    {
        foreach ($this->attributes[$method][Before::class] ?? [] as [$aspectService, $aspectMethod]) {
            $aspect = $aspectService();
            $callable = [$aspect, $aspectMethod];
            if (is_callable($callable)) {
                \call_user_func($callable, $joinPoint);
            }
            if ($joinPoint->isReturnEarly()) {
                break;
            }
            // 在 Before 阶段，我们可能会主动替换 instance 对象，以实现更加复杂的实例逻辑
            $instance = $joinPoint->getInstance();
        }

        return $instance;
    }

    /**
     * @param array<mixed> $params
     */
    private function executeTargetMethod(object $instance, string $method, array $params, JoinPoint $joinPoint): mixed
    {
        // FIX Redis有一些方法，需要特殊处理一次
        $params = $this->fixRedisMethodParams($instance, $method, $params);

        $callable = [$instance, $method];
        if (!is_callable($callable)) {
            throw new AopException(sprintf('Method %s::%s is not callable', get_class($instance), $method));
        }
        $returnValue = \call_user_func($callable, ...$params);
        $joinPoint->setReturnValue($returnValue);

        return $returnValue;
    }

    /**
     * @param array<mixed> $params
     * @return array<mixed>
     */
    private function fixRedisMethodParams(object $instance, string $method, array $params): array
    {
        if (!$instance instanceof \Redis) {
            return $params;
        }

        if ('info' === $method && isset($params['sections'])) {
            assert(is_array($params['sections']));

            return $params['sections'];
        }

        // 有相当一部分函数需要兼容，他们最后一参数是可选的，... $args 这样子定义的
        $lastParam = end($params);
        if (in_array(strtolower($method), $this->redisFixMethods, true) && is_array($lastParam)) {
            $extraParams = array_pop($params);
            if (is_array($extraParams) && [] !== $extraParams) {
                $params = array_merge($params, $extraParams);
                $params = array_values($params);
            }
        }

        return $params;
    }

    private function executeAfterReturningAdvices(JoinPoint $joinPoint, string $method): void
    {
        // @AfterReturning：如果目标方法成功执行（即没有抛出异常），则执行返回通知。
        foreach ($this->attributes[$method][AfterReturning::class] ?? [] as [$aspectService, $aspectMethod]) {
            $aspect = $aspectService();
            $callable = [$aspect, $aspectMethod];
            if (is_callable($callable)) {
                \call_user_func($callable, $joinPoint);
            }
        }
    }

    private function executeAfterThrowingAdvices(JoinPoint $joinPoint, string $method, \Throwable $exception): mixed
    {
        // @AfterThrowing：如果目标方法抛出异常，则执行异常通知。注意，如果目标方法成功执行，则不会执行此通知。
        $joinPoint->setException($exception);
        foreach ($this->attributes[$method][AfterThrowing::class] ?? [] as [$aspectService, $aspectMethod]) {
            $aspect = $aspectService();
            $callable = [$aspect, $aspectMethod];
            if (is_callable($callable)) {
                \call_user_func($callable, $joinPoint);
            }
            if ($joinPoint->isReturnEarly()) {
                return $joinPoint->getReturnValue();
            }
        }

        return null;
    }

    private function executeAfterAdvices(JoinPoint $joinPoint, string $method): void
    {
        // @After：最后，无论目标方法是否成功执行或抛出异常，都会执行后置通知。这是通知执行的最后阶段，通常用于执行一些清理工作、日志记录等。
        foreach ($this->attributes[$method][After::class] ?? [] as [$aspectService, $aspectMethod]) {
            $aspect = $aspectService();
            $callable = [$aspect, $aspectMethod];
            if (is_callable($callable)) {
                \call_user_func($callable, $joinPoint);
            }
            // After阶段不应该修改返回值，只用于清理工作
        }
    }

    /**
     * @param array<mixed> $params
     */
    private function executeAroundAdvice(JoinPoint $joinPoint, string $method, object $instance, array $params): mixed
    {
        $aroundAdvices = $this->attributes[$method][Around::class] ?? [];

        if ([] === $aroundAdvices) {
            return $this->executeTargetMethod($instance, $method, $params, $joinPoint);
        }

        $instance = $this->executeBeforeAdvices($joinPoint, $method, $instance);
        if ($joinPoint->isReturnEarly()) {
            return $joinPoint->getReturnValue();
        }

        return $this->buildAndExecuteAroundChain($aroundAdvices, $joinPoint, $instance, $method, $params);
    }

    /**
     * @param array<array{callable, string}> $aroundAdvices
     * @param array<mixed> $params
     */
    private function buildAndExecuteAroundChain(array $aroundAdvices, JoinPoint $joinPoint, object $instance, string $method, array $params): mixed
    {
        return $this->executeAroundAdviceChain($aroundAdvices, 0, $joinPoint, $instance, $method, $params);
    }

    /**
     * @param array<array{callable, string}> $aroundAdvices
     * @param array<mixed> $params
     */
    private function executeAroundAdviceChain(array $aroundAdvices, int $index, JoinPoint $joinPoint, object $instance, string $method, array $params): mixed
    {
        if ($index >= count($aroundAdvices)) {
            return $this->executeTargetMethodWithAdvices($instance, $method, $params, $joinPoint);
        }

        return $this->executeNextAroundAdvice($aroundAdvices, $index, $joinPoint, $instance, $method, $params);
    }

    /**
     * @param array<array{callable, string}> $aroundAdvices
     * @param array<mixed> $params
     */
    private function executeNextAroundAdvice(array $aroundAdvices, int $index, JoinPoint $joinPoint, object $instance, string $method, array $params): mixed
    {
        [$aspectService, $aspectMethod] = $aroundAdvices[$index];
        $aspect = $aspectService();
        $callable = [$aspect, $aspectMethod];

        if (!is_callable($callable)) {
            return $this->executeAroundAdviceChain($aroundAdvices, $index + 1, $joinPoint, $instance, $method, $params);
        }

        $proceedCallback = function () use ($aroundAdvices, $index, $joinPoint, $instance, $method, $params) {
            return $this->executeAroundAdviceChain($aroundAdvices, $index + 1, $joinPoint, $instance, $method, $params);
        };

        $joinPoint->setProceedCallback($proceedCallback);

        return \call_user_func($callable, $joinPoint);
    }

    /**
     * @param array<mixed> $params
     */
    private function executeTargetMethodWithAdvices(object $instance, string $method, array $params, JoinPoint $joinPoint): mixed
    {
        try {
            $returnValue = $this->executeTargetMethod($instance, $method, $params, $joinPoint);
            $this->executeAfterReturningAdvices($joinPoint, $method);

            return $returnValue;
        } catch (\Throwable $exception) {
            $earlyReturn = $this->executeAfterThrowingAdvices($joinPoint, $method, $exception);
            if (null !== $earlyReturn) {
                return $earlyReturn;
            }
            throw $exception;
        } finally {
            $this->executeAfterAdvices($joinPoint, $method);
        }
    }
}
