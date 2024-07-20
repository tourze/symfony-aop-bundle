<?php

namespace AopBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Advice
{
    public function __construct(public readonly string $statement)
    {
    }
}
