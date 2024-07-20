<?php

namespace AopBundle\Model;

/**
 * 参考Spring的AOP实现，有一些调整
 */
class JoinPoint
{
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

    private array $params;

    public function getParams(): array
    {
        return $this->params;
    }

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

    private string $serviceId;

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): void
    {
        $this->serviceId = $serviceId;
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
}
