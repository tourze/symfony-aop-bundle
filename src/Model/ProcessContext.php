<?php

namespace Tourze\Symfony\AOP\Model;

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
            static::$instances[$pid] = new static($pid);
        }
        return static::$instances[$pid];
    }
}
