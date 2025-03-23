<?php

namespace Tourze\Symfony\AOP\Attribute;

/**
 * 返回通知（AfterReturning）：在目标方法正常返回之后执行的通知。它通常用于在目标方法正常执行完成后进行一些处理，例如记录日志、返回结果处理等。
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AfterReturning extends Advice
{
}
