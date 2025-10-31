<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures;

class TargetService
{
    public function __construct()
    {
        // Constructor
    }

    public function doSomething(): string
    {
        return 'something';
    }

    public function doAnotherThing(): string
    {
        return 'another';
    }

    public function __destruct()
    {
        // Destructor
    }
}
