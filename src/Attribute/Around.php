<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Attribute;

#[\Attribute(flags: \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Around implements AdviceInterface
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
