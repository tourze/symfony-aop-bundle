<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Stopwatch;

class StopwatchTest extends TestCase
{
    public function testStopwatchAttribute(): void
    {
        $stopwatch = new Stopwatch();
        $this->assertInstanceOf(Stopwatch::class, $stopwatch);
    }

    public function testStopwatchIsRepeatable(): void
    {
        $reflectionClass = new \ReflectionClass(Stopwatch::class);
        $attributes = $reflectionClass->getAttributes();

        $found = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === \Attribute::class) {
                $instance = $attribute->newInstance();
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
            if ($attribute->getName() === \Attribute::class) {
                $instance = $attribute->newInstance();
                if (($instance->flags & \Attribute::TARGET_METHOD) !== 0) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, 'Stopwatch attribute should target methods');
    }
}
