<?php

namespace AopBundle\Attribute;

/**
 * 后置通知（After）：在目标方法执行之后执行的通知。它通常用于在目标方法执行之后进行一些清理工作，例如关闭数据库连接、释放资源等。
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class After extends Advice
{
}
