<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\Aop\Service\AspectMatchCache;

/**
 * @internal
 */
#[CoversClass(AspectMatchCache::class)]
#[RunTestsInSeparateProcesses] final class AspectMatchCacheTest extends AbstractIntegrationTestCase
{
    private AspectMatchCache $cache;

    protected function onSetUp(): void
    {
        $this->cache = self::getService(AspectMatchCache::class);
    }

    public function testGetAndSet(): void
    {
        $aspectConfig = ['advice' => 'Before', 'method' => 'test'];
        $this->cache->set('service.id', 'method', $aspectConfig);

        $result = $this->cache->get('service.id', 'method');
        $this->assertEquals($aspectConfig, $result);
    }

    public function testGetNonExistent(): void
    {
        $result = $this->cache->get('nonexistent', 'method');
        $this->assertNull($result);
    }

    public function testCacheHitsMisses(): void
    {
        // First access - miss
        $this->cache->get('service1', 'method1');

        // Set and get - hit
        $this->cache->set('service1', 'method1', ['test' => 'data']);
        $this->cache->get('service1', 'method1');

        // Another miss
        $this->cache->get('service2', 'method2');

        $stats = $this->cache->getStatistics();
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(2, $stats['misses']);
        $this->assertEqualsWithDelta(33.33, $stats['hit_rate'], 0.01);
    }

    public function testClear(): void
    {
        $this->cache->set('service1', 'method1', ['data1' => 'value1']);
        $this->cache->set('service2', 'method2', ['data2' => 'value2']);

        $this->cache->clear();

        $this->assertNull($this->cache->get('service1', 'method1'));
        $this->assertNull($this->cache->get('service2', 'method2'));

        $stats = $this->cache->getStatistics();
        $this->assertEquals(0, $stats['memory_cache_size']);
    }

    public function testDisabled(): void
    {
        $this->cache->setEnabled(false);
        $this->assertFalse($this->cache->isEnabled());

        $this->cache->set('service', 'method', ['data' => 'value']);
        $result = $this->cache->get('service', 'method');

        $this->assertNull($result);
    }

    public function testWithPersistentCache(): void
    {
        // 测试持久化缓存的基本功能，由于使用容器服务无法mock依赖，改为测试基本功能
        $cache = self::getService(AspectMatchCache::class);

        // 设置缓存数据
        $cache->set('persistent.service', 'method', ['persistent' => 'data']);

        // 获取缓存数据
        $result = $cache->get('persistent.service', 'method');

        $this->assertEquals(['persistent' => 'data'], $result);
    }

    public function testSetWithPersistentCache(): void
    {
        // 测试设置持久化缓存的基本功能，由于使用容器服务无法mock依赖，改为功能性测试
        $cache = self::getService(AspectMatchCache::class);

        // 设置缓存数据应该不会抛出异常
        $cache->set('persistent.service2', 'method2', ['new' => 'data']);

        // 验证数据被正确设置
        $result = $cache->get('persistent.service2', 'method2');
        $this->assertEquals(['new' => 'data'], $result);
    }

    public function testClearWithPersistentCache(): void
    {
        $cache = self::getService(AspectMatchCache::class);

        // 先设置一些数据
        $cache->set('persistent.service3', 'method3', ['data' => 'value']);

        // 验证数据存在
        $this->assertEquals(['data' => 'value'], $cache->get('persistent.service3', 'method3'));

        // 清空缓存
        $cache->clear();

        // 验证数据被清空
        $this->assertNull($cache->get('persistent.service3', 'method3'));

        $stats = $cache->getStatistics();
        $this->assertEquals(0, $stats['memory_cache_size']);
    }

    public function testStatisticsWithNoRequests(): void
    {
        $stats = $this->cache->getStatistics();
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0, $stats['hit_rate']);
        $this->assertEquals(0, $stats['memory_cache_size']);
        $this->assertTrue($stats['enabled']);
    }
}
