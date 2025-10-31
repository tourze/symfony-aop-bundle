<?php

namespace Tourze\Symfony\Aop\Service;

class AopMetricsCollector
{
    /**
     * @var array<string, array{count: int, total_time: float, min_time: float, max_time: float, avg_time: float}>
     */
    private array $metrics = [];

    /**
     * @var array<string, float>
     */
    private array $activeTimers = [];

    private bool $enabled = true;

    public function startTimer(string $aspectKey): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->activeTimers[$aspectKey] = microtime(true);
    }

    public function stopTimer(string $aspectKey): void
    {
        if (!$this->enabled || !isset($this->activeTimers[$aspectKey])) {
            return;
        }

        $duration = microtime(true) - $this->activeTimers[$aspectKey];
        unset($this->activeTimers[$aspectKey]);

        $this->recordExecution($aspectKey, $duration);
    }

    public function recordExecution(string $aspectKey, float $duration): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->metrics[$aspectKey])) {
            $this->metrics[$aspectKey] = [
                'count' => 0,
                'total_time' => 0.0,
                'min_time' => PHP_FLOAT_MAX,
                'max_time' => 0.0,
                'avg_time' => 0.0,
            ];
        }

        ++$this->metrics[$aspectKey]['count'];
        $this->metrics[$aspectKey]['total_time'] += $duration;
        $this->metrics[$aspectKey]['min_time'] = min($this->metrics[$aspectKey]['min_time'], $duration);
        $this->metrics[$aspectKey]['max_time'] = max($this->metrics[$aspectKey]['max_time'], $duration);
        $this->metrics[$aspectKey]['avg_time'] = $this->metrics[$aspectKey]['total_time'] / $this->metrics[$aspectKey]['count'];
    }

    /**
     * @return array<string, array{count: int, total_time: float, min_time: float, max_time: float, avg_time: float}>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * @return array{count: int, total_time: float, min_time: float, max_time: float, avg_time: float}|null
     */
    public function getMetricsForAspect(string $aspectKey): ?array
    {
        return $this->metrics[$aspectKey] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $totalExecutions = 0;
        $totalTime = 0.0;
        $slowestAspect = null;
        $slowestTime = 0.0;
        $mostFrequentAspect = null;
        $mostFrequentCount = 0;

        foreach ($this->metrics as $aspectKey => $metric) {
            $totalExecutions += $metric['count'];
            $totalTime += $metric['total_time'];

            if ($metric['avg_time'] > $slowestTime) {
                $slowestTime = $metric['avg_time'];
                $slowestAspect = $aspectKey;
            }

            if ($metric['count'] > $mostFrequentCount) {
                $mostFrequentCount = $metric['count'];
                $mostFrequentAspect = $aspectKey;
            }
        }

        return [
            'total_aspects' => count($this->metrics),
            'total_executions' => $totalExecutions,
            'total_time' => $totalTime,
            'average_time' => $totalExecutions > 0 ? $totalTime / $totalExecutions : 0,
            'slowest_aspect' => $slowestAspect,
            'slowest_avg_time' => $slowestTime,
            'most_frequent_aspect' => $mostFrequentAspect,
            'most_frequent_count' => $mostFrequentCount,
            'enabled' => $this->enabled,
        ];
    }

    public function reset(): void
    {
        $this->metrics = [];
        $this->activeTimers = [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * 导出性能数据为可读格式
     *
     * @return array<int, array<string, mixed>>
     */
    public function export(): array
    {
        $result = [];

        foreach ($this->metrics as $aspectKey => $metric) {
            $result[] = [
                'aspect' => $aspectKey,
                'count' => $metric['count'],
                'total_time_ms' => round($metric['total_time'] * 1000, 3),
                'avg_time_ms' => round($metric['avg_time'] * 1000, 3),
                'min_time_ms' => round($metric['min_time'] * 1000, 3),
                'max_time_ms' => round($metric['max_time'] * 1000, 3),
            ];
        }

        // 按平均时间降序排序
        usort($result, function ($a, $b) {
            return $b['avg_time_ms'] <=> $a['avg_time_ms'];
        });

        return $result;
    }
}
