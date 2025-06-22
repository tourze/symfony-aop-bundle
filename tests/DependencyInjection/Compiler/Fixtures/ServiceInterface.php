<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

interface ServiceInterface
{
    public function execute(): void;
}