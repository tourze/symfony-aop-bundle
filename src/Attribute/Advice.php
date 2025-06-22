<?php

namespace Tourze\Symfony\Aop\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Advice
{
    public function __construct(
        public ?string $statement = null,
        public ?string $classAttribute = null,
        public ?string $methodAttribute = null,
        public ?array $serviceIds = null,
        public ?array $serviceTags = null,
        public ?array $parentClasses = null,
    ) {
        if ($this->classAttribute !== null) {
            $this->statement = self::getClassAttributeStatement($this->classAttribute);
        }
        if ($this->methodAttribute !== null) {
            $this->statement = self::getMethodAttributeStatement($this->methodAttribute);
        }
        if ($this->serviceIds !== null) {
            $this->statement = self::getServiceIdStatement($this->serviceIds);
        }
        if ($this->serviceTags !== null) {
            $this->statement = self::getServiceTagStatement($this->serviceTags);
        }
        if ($this->parentClasses !== null) {
            $this->statement = self::getParentClassStatement($this->parentClasses);
        }
    }

    private static function getClassAttributeStatement(string $name): string
    {
        $name = str_replace('\\', '\\\\', $name);
        return "count(class.getAttributes('$name')) > 0";
    }

    private static function getMethodAttributeStatement(string $name): string
    {
        $name = str_replace('\\', '\\\\', $name);
        return "count(method.getAttributes('$name')) > 0";
    }

    private static function getServiceIdStatement(array $serviceIds): string
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

        return implode(" || ", $statements);
    }

    private static function getServiceTagStatement(array $tags): string
    {
        $lines = [];
        foreach ($tags as $tag) {
            $lines[] = "('{$tag}' in serviceTags)";
        }
        return implode(" || ", $lines);
    }

    private static function getParentClassStatement(array $parentClasses): string
    {
        $part1 = '!class.isFinal()';

        $list = [];
        foreach ($parentClasses as $parentClass) {
            $name = str_replace('\\', '\\\\', $parentClass);
            $list[] = "('{$name}' in parentClasses)";
        }
        $part2 = implode(" || ", $list);
        return "{$part1} && ({$part2})";
    }
}
