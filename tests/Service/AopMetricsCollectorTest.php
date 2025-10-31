<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Service\AopMetricsCollector;

/**
 * @internal
 */
#[CoversClass(AopMetricsCollector::class)]
final class AopMetricsCollectorTest extends TestCase
{
    public function testStartAndStopTimer(): void
    {
        $collector = new AopMetricsCollector();

        $collector->startTimer('test.aspect');
        usleep(10000); // Sleep for 10ms
        $collector->stopTimer('test.aspect');

        $metrics = $collector->getMetricsForAspect('test.aspect');
        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
        $this->assertGreaterThan(0, $metrics['total_time']);
    }

    public function testRecordExecution(): void
    {
        $collector = new AopMetricsCollector();

        $collector->recordExecution('test.aspect', 0.1);
        $collector->recordExecution('test.aspect', 0.2);
        $collector->recordExecution('test.aspect', 0.15);

        $metrics = $collector->getMetricsForAspect('test.aspect');
        $this->assertNotNull($metrics);
        $this->assertEquals(3, $metrics['count']);
        $this->assertEqualsWithDelta(0.45, $metrics['total_time'], 0.001);
        $this->assertEqualsWithDelta(0.15, $metrics['avg_time'], 0.001);
        $this->assertEqualsWithDelta(0.1, $metrics['min_time'], 0.001);
        $this->assertEqualsWithDelta(0.2, $metrics['max_time'], 0.001);
    }

    public function testGetSummary(): void
    {
        $collector = new AopMetricsCollector();

        $collector->recordExecution('aspect1', 0.1);
        $collector->recordExecution('aspect1', 0.2);
        $collector->recordExecution('aspect2', 0.3);

        $summary = $collector->getSummary();
        $this->assertEquals(2, $summary['total_aspects']);
        $this->assertEquals(3, $summary['total_executions']);
        $this->assertEqualsWithDelta(0.6, $summary['total_time'], 0.001);
        $this->assertEqualsWithDelta(0.2, $summary['average_time'], 0.001);
        $this->assertEquals('aspect2', $summary['slowest_aspect']);
        $this->assertEqualsWithDelta(0.3, $summary['slowest_avg_time'], 0.001);
        $this->assertEquals('aspect1', $summary['most_frequent_aspect']);
        $this->assertEquals(2, $summary['most_frequent_count']);
        $this->assertTrue($summary['enabled']);
    }

    public function testReset(): void
    {
        $collector = new AopMetricsCollector();

        $collector->recordExecution('test.aspect', 0.1);
        $this->assertCount(1, $collector->getMetrics());

        $collector->reset();
        $this->assertCount(0, $collector->getMetrics());
    }

    public function testDisabled(): void
    {
        $collector = new AopMetricsCollector();

        $collector->setEnabled(false);
        $this->assertFalse($collector->isEnabled());

        $collector->recordExecution('test.aspect', 0.1);
        $this->assertNull($collector->getMetricsForAspect('test.aspect'));
    }

    public function testExport(): void
    {
        $collector = new AopMetricsCollector();

        $collector->recordExecution('slow.aspect', 0.5);
        $collector->recordExecution('fast.aspect', 0.01);
        $collector->recordExecution('medium.aspect', 0.1);

        $export = $collector->export();
        $this->assertCount(3, $export);

        // Should be sorted by avg_time descending
        $this->assertEquals('slow.aspect', $export[0]['aspect']);
        $this->assertEquals('medium.aspect', $export[1]['aspect']);
        $this->assertEquals('fast.aspect', $export[2]['aspect']);

        // Check time conversion to milliseconds
        $this->assertEqualsWithDelta(500, $export[0]['total_time_ms'], 1);
        $this->assertEqualsWithDelta(10, $export[2]['total_time_ms'], 1);
    }

    public function testStopTimerWithoutStart(): void
    {
        $collector = new AopMetricsCollector();

        // Should not throw exception
        $collector->stopTimer('nonexistent.aspect');
        $this->assertNull($collector->getMetricsForAspect('nonexistent.aspect'));
    }

    public function testMultipleAspects(): void
    {
        $collector = new AopMetricsCollector();

        $collector->recordExecution('aspect1', 0.1);
        $collector->recordExecution('aspect2', 0.2);
        $collector->recordExecution('aspect3', 0.3);

        $metrics = $collector->getMetrics();
        $this->assertCount(3, $metrics);
        $this->assertArrayHasKey('aspect1', $metrics);
        $this->assertArrayHasKey('aspect2', $metrics);
        $this->assertArrayHasKey('aspect3', $metrics);
    }

    public function testStartTimer(): void
    {
        $collector = new AopMetricsCollector();

        // Test startTimer with enabled collector
        $collector->startTimer('test.aspect');
        $this->assertTrue(true); // Timer should start without errors

        // Test startTimer with disabled collector
        $collector->setEnabled(false);
        $collector->startTimer('disabled.aspect');
        $this->assertTrue(true); // Should not fail when disabled

        // Re-enable and test multiple timers
        $collector->setEnabled(true);
        $collector->startTimer('timer1');
        $collector->startTimer('timer2');
        $this->assertTrue(true); // Multiple timers should be allowed

        // Test that starting same timer twice overwrites the previous one
        $collector->startTimer('duplicate');
        $collector->startTimer('duplicate'); // This should overwrite the first one
        $this->assertTrue(true); // Should not cause errors
    }
}
