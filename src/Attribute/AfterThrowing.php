<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Attribute;

/**
 * 异常通知（AfterThrowing）：在目标方法抛出异常之后执行的通知。它通常用于在目标方法抛出异常后进行一些处理，例如记录异常日志、回滚事务等。
 */
#[\Attribute(flags: \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AfterThrowing implements AdviceInterface
{
    use AdviceTrait;

    /**
     * @param array<string>|null $serviceIds
     * @param array<string>|null $serviceTags
     * @param array<string>|null $parentClasses
     */
    public function __construct(
        ?string $statement = null,
        ?string $classAttribute = null,
        ?string $methodAttribute = null,
        ?array $serviceIds = null,
        ?array $serviceTags = null,
        ?array $parentClasses = null,
    ) {
        $this->statement = $statement;
        $this->classAttribute = $classAttribute;
        $this->methodAttribute = $methodAttribute;
        $this->serviceIds = $serviceIds;
        $this->serviceTags = $serviceTags;
        $this->parentClasses = $parentClasses;

        $this->initializeAdvice();
    }
}
