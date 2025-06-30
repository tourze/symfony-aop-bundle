<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

/** @phpstan-ignore-next-line */
interface ServiceInterface
{
    public function execute(): void;
}