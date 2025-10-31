<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Stopwatch;

/**
 * @internal
 */
#[CoversClass(Stopwatch::class)]
final class StopwatchTest extends TestCase
{
    public function testStopwatchAttribute(): void
    {
        // Stopwatch is a simple marker attribute
        $stopwatch = new Stopwatch();
        // Since it's a plain class with no properties, just verify it can be instantiated
        $this->assertInstanceOf(Stopwatch::class, $stopwatch);
    }

    public function testStopwatchIsRepeatable(): void
    {
        $reflectionClass = new \ReflectionClass(Stopwatch::class);
        $attributes = $reflectionClass->getAttributes();

        $found = false;
        foreach ($attributes as $attribute) {
            if (\Attribute::class === $attribute->getName()) {
                $instance = $attribute->newInstance();
                /** @var \Attribute $instance */
                if (($instance->flags & \Attribute::IS_REPEATABLE) !== 0) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, 'Stopwatch attribute should be repeatable');
    }

    public function testStopwatchTargetsMethod(): void
    {
        $reflectionClass = new \ReflectionClass(Stopwatch::class);
        $attributes = $reflectionClass->getAttributes();

        $found = false;
        foreach ($attributes as $attribute) {
            if (\Attribute::class === $attribute->getName()) {
                $instance = $attribute->newInstance();
                /** @var \Attribute $instance */
                if (($instance->flags & \Attribute::TARGET_METHOD) !== 0) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, 'Stopwatch attribute should target methods');
    }
}
