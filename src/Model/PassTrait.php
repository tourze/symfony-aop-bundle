<?php

namespace Tourze\Symfony\AOP\Model;

trait PassTrait
{
    /**
     * @var string|null 这里我们存放的是原始的服务ID，没被代理过的
     */
    private ?string $internalServiceId = null;

    public function getInternalServiceId(): ?string
    {
        return $this->internalServiceId;
    }

    public function setInternalServiceId(string $internalServiceId): void
    {
        $this->internalServiceId = $internalServiceId;
    }

    /**
     * @var string|null 这里代表代理后的服务ID
     */
    private ?string $proxyServiceId = null;

    public function getProxyServiceId(): ?string
    {
        return $this->proxyServiceId;
    }

    public function setProxyServiceId(?string $proxyServiceId): void
    {
        $this->proxyServiceId = $proxyServiceId;
    }

    private object|string|null $factoryInstance = null;

    public function getFactoryInstance(): object|string|null
    {
        return $this->factoryInstance;
    }

    public function setFactoryInstance(object|string|null $factoryInstance): void
    {
        $this->factoryInstance = $factoryInstance;
    }

    private ?string $factoryMethod = null;

    public function getFactoryMethod(): ?string
    {
        return $this->factoryMethod;
    }

    public function setFactoryMethod(?string $factoryMethod): void
    {
        $this->factoryMethod = $factoryMethod;
    }

    private ?array $factoryArguments = null;

    public function getFactoryArguments(): ?array
    {
        return $this->factoryArguments;
    }

    public function setFactoryArguments(?array $factoryArguments): void
    {
        $this->factoryArguments = $factoryArguments;
    }
}
