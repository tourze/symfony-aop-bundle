<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

class ServiceWithFactoryClass
{
    private string $arg1;
    private string $arg2;

    public function __construct(string $arg1, string $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }

    public function process(): string
    {
        return $this->arg1 . ' ' . $this->arg2;
    }
}