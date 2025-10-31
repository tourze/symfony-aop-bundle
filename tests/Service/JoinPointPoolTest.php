<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Service\JoinPointPool;

/**
 * @internal
 */
#[CoversClass(JoinPointPool::class)]
final class JoinPointPoolTest extends TestCase
{
    public function testAcquireCreatesNewJoinPoint(): void
    {
        $pool = new JoinPointPool(5);

        $joinPoint = $pool->acquire();
        $this->assertInstanceOf(JoinPoint::class, $joinPoint);

        $stats = $pool->getStatistics();
        $this->assertEquals(1, $stats['total_created']);
        $this->assertEquals(0, $stats['total_reused']);
    }

    public function testReleaseAndReuseJoinPoint(): void
    {
        $pool = new JoinPointPool(5);

        $joinPoint1 = $pool->acquire();
        $joinPoint1->setReturnValue('test');
        $pool->release($joinPoint1);

        $joinPoint2 = $pool->acquire();

        $this->assertSame($joinPoint1, $joinPoint2);
        $this->assertNull($joinPoint2->getReturnValue()); // Should be reset

        $stats = $pool->getStatistics();
        $this->assertEquals(1, $stats['total_created']);
        $this->assertEquals(1, $stats['total_reused']);
    }

    public function testPoolSizeLimit(): void
    {
        $pool = new JoinPointPool(5);

        // Fill pool to max size
        for ($i = 0; $i < 10; ++$i) {
            $jp = $pool->acquire();
            $pool->release($jp);
        }

        $stats = $pool->getStatistics();
        $this->assertLessThanOrEqual(5, $stats['pool_size']); // Max pool size is 5
    }

    public function testClearPool(): void
    {
        $pool = new JoinPointPool(5);

        for ($i = 0; $i < 3; ++$i) {
            $jp = $pool->acquire();
            $pool->release($jp);
        }

        $pool->clear();

        $stats = $pool->getStatistics();
        $this->assertEquals(0, $stats['pool_size']);
    }

    public function testReuseRate(): void
    {
        $pool = new JoinPointPool(5);

        // Create first JoinPoint
        $jp1 = $pool->acquire();
        $pool->release($jp1);

        // Reuse it
        $jp2 = $pool->acquire();

        $stats = $pool->getStatistics();
        $this->assertEquals(50.0, $stats['reuse_rate']); // 1 created, 1 reused = 50%
    }
}
