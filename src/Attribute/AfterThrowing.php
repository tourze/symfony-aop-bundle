<?php

namespace Tourze\Symfony\Aop\Attribute;

/**
 * 异常通知（AfterThrowing）：在目标方法抛出异常之后执行的通知。它通常用于在目标方法抛出异常后进行一些处理，例如记录异常日志、回滚事务等。
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AfterThrowing extends Advice
{
}
