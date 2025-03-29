<?php

namespace Tourze\Symfony\Aop\Attribute;

/**
 * 前置通知（Before）：在目标方法执行之前执行的通知。它通常用于在目标方法执行之前进行一些准备工作，例如权限校验、日志记录等。
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Before extends Advice
{
}
