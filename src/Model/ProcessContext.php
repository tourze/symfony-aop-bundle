<?php

namespace Tourze\Symfony\Aop\Model;

class ProcessContext
{
    public function __construct(private readonly int $pid)
    {
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    private static array $instances = [];

    public static function instance(int $pid): ProcessContext
    {
        if (!isset(static::$instances[$pid])) {
            static::$instances[$pid] = new self($pid);
        }
        return static::$instances[$pid];
    }
}
