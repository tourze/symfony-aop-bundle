<?php

namespace Tourze\Symfony\Aop\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class AspectMatchCache
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $memoryCache = [];

    private ?CacheItemPoolInterface $persistentCache;

    private bool $enabled = true;

    private int $hits = 0;

    private int $misses = 0;

    public function __construct(?CacheItemPoolInterface $persistentCache = null)
    {
        $this->persistentCache = $persistentCache;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $serviceId, string $method): ?array
    {
        if (!$this->enabled) {
            ++$this->misses;

            return null;
        }

        $key = $this->getCacheKey($serviceId, $method);

        // 首先检查内存缓存
        if (isset($this->memoryCache[$key])) {
            ++$this->hits;

            return $this->memoryCache[$key];
        }

        // 检查持久化缓存
        if (null !== $this->persistentCache) {
            $item = $this->persistentCache->getItem($key);
            if ($item->isHit()) {
                $value = $item->get();
                assert(is_array($value));
                /** @var array<string, mixed> $value */
                $this->memoryCache[$key] = $value;
                ++$this->hits;

                return $value;
            }
        }

        ++$this->misses;

        return null;
    }

    private function getCacheKey(string $serviceId, string $method): string
    {
        return 'aop_match_' . md5($serviceId . '::' . $method);
    }

    /**
     * @param array<string, mixed> $aspectConfig
     */
    public function set(string $serviceId, string $method, array $aspectConfig): void
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->getCacheKey($serviceId, $method);
        $this->memoryCache[$key] = $aspectConfig;

        // 存储到持久化缓存
        if (null !== $this->persistentCache) {
            $item = $this->persistentCache->getItem($key);
            $item->set($aspectConfig);
            $item->expiresAfter(3600); // 缓存1小时
            $this->persistentCache->save($item);
        }
    }

    public function clear(): void
    {
        $this->memoryCache = [];
        if (null !== $this->persistentCache) {
            $this->persistentCache->clear();
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /** @return array<string, mixed> */
    public function getStatistics(): array
    {
        $total = $this->hits + $this->misses;

        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $total > 0 ? round($this->hits / $total * 100, 2) : 0,
            'memory_cache_size' => count($this->memoryCache),
            'enabled' => $this->enabled,
        ];
    }
}
