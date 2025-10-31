<?php

namespace Tourze\Symfony\Aop\Attribute;

/**
 * 更加智能地添加stopwatch
 */
#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Stopwatch
{
}
