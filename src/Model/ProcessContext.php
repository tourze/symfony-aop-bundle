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

    /**
     * @var array<int, ProcessContext>
     */
    private static array $instances = [];

    public static function instance(int $pid): ProcessContext
    {
        if (!isset(self::$instances[$pid])) {
            self::$instances[$pid] = new self($pid);
        }

        return self::$instances[$pid];
    }
}
