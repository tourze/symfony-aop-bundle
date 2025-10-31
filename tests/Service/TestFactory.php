<?php

namespace Tourze\Symfony\Aop\Tests\Service;

/**
 * 辅助类：测试用的工厂类
 */
class TestFactory
{
    public string $value;

    public static function createInstance(string $value): self
    {
        $instance = new self();
        $instance->value = $value;

        return $instance;
    }

    public function createInstanceMethod(string $value): self
    {
        $instance = new self();
        $instance->value = $value;

        return $instance;
    }
}
