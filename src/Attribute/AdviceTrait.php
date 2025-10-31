<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Attribute;

trait AdviceTrait
{
    public ?string $statement = null;

    public ?string $classAttribute = null;

    public ?string $methodAttribute = null;

    /** @var array<string>|null */
    public ?array $serviceIds = null;

    /** @var array<string>|null */
    public ?array $serviceTags = null;

    /** @var array<string>|null */
    public ?array $parentClasses = null;

    public function initializeAdvice(): void
    {
        if (null !== $this->classAttribute) {
            $this->statement = $this->getClassAttributeStatement($this->classAttribute);
        }
        if (null !== $this->methodAttribute) {
            $this->statement = $this->getMethodAttributeStatement($this->methodAttribute);
        }
        if (null !== $this->serviceIds) {
            $this->statement = $this->getServiceIdStatement($this->serviceIds);
        }
        if (null !== $this->serviceTags) {
            $this->statement = $this->getServiceTagStatement($this->serviceTags);
        }
        if (null !== $this->parentClasses) {
            $this->statement = $this->getParentClassStatement($this->parentClasses);
        }
    }

    public function getStatement(): ?string
    {
        return $this->statement;
    }

    public function getClassAttribute(): ?string
    {
        return $this->classAttribute;
    }

    public function getMethodAttribute(): ?string
    {
        return $this->methodAttribute;
    }

    /** @return array<string>|null */
    public function getServiceIds(): ?array
    {
        return $this->serviceIds;
    }

    /** @return array<string>|null */
    public function getServiceTags(): ?array
    {
        return $this->serviceTags;
    }

    /** @return array<string>|null */
    public function getParentClasses(): ?array
    {
        return $this->parentClasses;
    }

    private function getClassAttributeStatement(string $name): string
    {
        $name = str_replace('\\', '\\\\', $name);

        return "count(class.getAttributes('{$name}')) > 0";
    }

    private function getMethodAttributeStatement(string $name): string
    {
        $name = str_replace('\\', '\\\\', $name);

        return "count(method.getAttributes('{$name}')) > 0";
    }

    /**
     * @param array<string> $serviceIds
     */
    private function getServiceIdStatement(array $serviceIds): string
    {
        // 这里要区分带通配符的情形
        $fullList = [];
        $prefixList = [];
        $suffixList = [];

        foreach ($serviceIds as $serviceId) {
            $f = true;
            if (str_starts_with($serviceId, '*')) {
                $prefixList[] = trim($serviceId, '*');
                $f = false;
            }
            if (str_ends_with($serviceId, '*')) {
                $suffixList[] = trim($serviceId, '*');
                $f = false;
            }
            if ($f) {
                $fullList[] = $serviceId;
            }
        }

        // 不同部分组装为一个很大的 or 语句
        $statements = [];

        // 第一部分
        $part1Service = "'" . implode("', '", $fullList) . "'";
        $statements[] = "(serviceId in [{$part1Service}])";

        // 前缀匹配
        foreach ($prefixList as $item) {
            $statements[] = "(serviceId ends with '{$item}')";
        }

        // 后缀匹配
        foreach ($suffixList as $item) {
            $statements[] = "(serviceId starts with '{$item}')";
        }

        return implode(' || ', $statements);
    }

    /**
     * @param array<string> $tags
     */
    private function getServiceTagStatement(array $tags): string
    {
        $lines = [];
        foreach ($tags as $tag) {
            $lines[] = "('{$tag}' in serviceTags)";
        }

        return implode(' || ', $lines);
    }

    /**
     * @param array<string> $parentClasses
     */
    private function getParentClassStatement(array $parentClasses): string
    {
        $part1 = '!class.isFinal()';

        $list = [];
        foreach ($parentClasses as $parentClass) {
            $name = str_replace('\\', '\\\\', $parentClass);
            $list[] = "('{$name}' in parentClasses)";
        }
        $part2 = implode(' || ', $list);

        return "{$part1} && ({$part2})";
    }
}
