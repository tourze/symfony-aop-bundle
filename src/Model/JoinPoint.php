<?php

namespace Tourze\Symfony\Aop\Model;

use Tourze\Arrayable\Arrayable;
use Tourze\Symfony\Aop\Exception\AopException;

/**
 * 参考Spring的AOP实现，有一些调整
 * @implements Arrayable<string, mixed>
 */
class JoinPoint implements Arrayable
{
    use PassTrait;

    private object $proxy;

    public function getProxy(): object
    {
        return $this->proxy;
    }

    public function setProxy(object $proxy): void
    {
        $this->proxy = $proxy;
    }

    private object $instance;

    public function getInstance(): object
    {
        return $this->instance;
    }

    public function setInstance(object $instance): void
    {
        $this->instance = $instance;
    }

    private string $method;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @var array<mixed>
     */
    private array $params;

    /**
     * @return array<mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    private bool $returnEarly = false;

    public function isReturnEarly(): bool
    {
        return $this->returnEarly;
    }

    public function setReturnEarly(bool $returnEarly): void
    {
        $this->returnEarly = $returnEarly;
    }

    private mixed $returnValue = null;

    public function getReturnValue(): mixed
    {
        return $this->returnValue;
    }

    public function setReturnValue(mixed $returnValue): void
    {
        $this->returnValue = $returnValue;
    }

    private int $sequenceId;

    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    public function setSequenceId(int $sequenceId): void
    {
        $this->sequenceId = $sequenceId;
    }

    private ?\Throwable $exception = null;

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function setException(?\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * 根据服务/方法/参数自动计算一个唯一ID
     */
    public function getUniqueId(): string
    {
        $parts = [
            $this->getInternalServiceId(),
            // get_class($this->getInstance()),
            $this->getMethod(),
            md5(serialize($this->getParams())),
        ];
        $parts = implode('_', $parts);

        // 要过滤一些特殊字符
        return str_replace(['\\', '/', '!', ':', '@', '#', '$', '%', '^', '&', '*', '(', ')', '[', ']', '|', ',', ';', "'", '"'], '_', $parts);
    }

    private ?\Closure $proceedCallback = null;

    public function setProceedCallback(?\Closure $callback): void
    {
        $this->proceedCallback = $callback;
    }

    /**
     * 执行
     */
    public function proceed(): mixed
    {
        // 如果设置了Around通知的proceed回调，使用它
        if (null !== $this->proceedCallback) {
            return ($this->proceedCallback)();
        }

        // 否则直接执行目标方法
        $object = $this->getInstance();
        $method = $this->getMethod();
        $params = $this->getParams();

        $callable = [$object, $method];
        if (!is_callable($callable)) {
            throw new AopException(sprintf('Method %s::%s is not callable', get_class($object), $method));
        }

        return \call_user_func($callable, ...$params);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'serviceId' => $this->getInternalServiceId(),
            'method' => $this->getMethod(),
        ];
    }
}
