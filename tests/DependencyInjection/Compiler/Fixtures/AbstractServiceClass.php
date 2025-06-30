<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

/** @phpstan-ignore-next-line */
abstract class AbstractServiceClass
{
    abstract public function process(): void;
}