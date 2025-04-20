<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use Tourze\Symfony\Aop\Attribute\Advice;

/**
 * 自定义通知类型，用于测试
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CustomAdvice extends Advice
{
}
