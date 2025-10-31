<?php

namespace Tourze\Symfony\Aop\Service;

use Tourze\Symfony\Aop\Model\JoinPoint;

class JoinPointPool
{
    /**
     * @var array<JoinPoint>
     */
    private array $pool = [];

    private int $maxPoolSize = 100;

    private int $created = 0;

    private int $reused = 0;

    public function __construct(int $maxPoolSize = 100)
    {
        $this->maxPoolSize = $maxPoolSize;
    }

    public function acquire(): JoinPoint
    {
        if ([] !== $this->pool) {
            $joinPoint = array_pop($this->pool);
            ++$this->reused;

            return $joinPoint;
        }

        ++$this->created;

        return new JoinPoint();
    }

    public function release(JoinPoint $joinPoint): void
    {
        if (count($this->pool) >= $this->maxPoolSize) {
            return;
        }

        // 重置JoinPoint状态
        $this->reset($joinPoint);
        $this->pool[] = $joinPoint;
    }

    private function reset(JoinPoint $joinPoint): void
    {
        $joinPoint->setReturnEarly(false);
        $joinPoint->setReturnValue(null);
        $joinPoint->setException(null);
        $joinPoint->setProceedCallback(null);
        // 注意：不重置proxy、instance、method、params等核心属性
        // 因为它们会在下次使用时被重新设置
    }

    /** @return array<string, mixed> */
    public function getStatistics(): array
    {
        return [
            'pool_size' => count($this->pool),
            'max_pool_size' => $this->maxPoolSize,
            'total_created' => $this->created,
            'total_reused' => $this->reused,
            'reuse_rate' => $this->created > 0 ? round($this->reused / ($this->created + $this->reused) * 100, 2) : 0,
        ];
    }

    public function clear(): void
    {
        $this->pool = [];
    }
}
