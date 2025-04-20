<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

/**
 * 用于测试 AOP 拦截的服务类
 */
class TestService
{
    private array $log = [];

    public function getLog(): array
    {
        return $this->log;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    public function addLog(string $message): void
    {
        $this->log[] = $message;
    }

    /**
     * 普通方法，用于测试 AOP 拦截
     */
    public function normalMethod(string $param1, int $param2): string
    {
        $this->addLog("normalMethod executed with $param1 and $param2");
        return "Result: $param1-$param2";
    }

    /**
     * 抛出异常的方法，用于测试异常通知
     */
    public function exceptionMethod(string $message): void
    {
        $this->addLog("exceptionMethod about to throw exception with message: $message");
        throw new \RuntimeException($message);
    }
}
