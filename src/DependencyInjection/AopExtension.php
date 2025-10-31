<?php

namespace Tourze\Symfony\Aop\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class AopExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
