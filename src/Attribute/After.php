<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Attribute;

/**
 * 后置通知（After）：在目标方法执行之后执行的通知。它通常用于在目标方法执行之后进行一些清理工作，例如关闭数据库连接、释放资源等。
 */
#[\Attribute(flags: \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class After implements AdviceInterface
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
