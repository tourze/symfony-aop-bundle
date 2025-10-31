<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Around;

/**
 * @internal
 */
#[CoversClass(Around::class)]
final class AroundTest extends TestCase
{
    public function testAroundAttribute(): void
    {
        $around = new Around('class.getName() === "TestClass"');
        $this->assertEquals('class.getName() === "TestClass"', $around->statement);
    }

    public function testAroundIsRepeatable(): void
    {
        $reflection = new \ReflectionClass(Around::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();

        $this->assertEquals(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE, $attribute->flags);
    }

    public function testAroundTargetsMethod(): void
    {
        $reflection = new \ReflectionClass(Around::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();

        $this->assertTrue(($attribute->flags & \Attribute::TARGET_METHOD) === \Attribute::TARGET_METHOD);
    }

    public function testAroundWithServiceIds(): void
    {
        $around = new Around(serviceIds: ['service1', 'service2']);
        $this->assertNotNull($around->statement);
        $this->assertStringContainsString('service1', $around->statement);
        $this->assertStringContainsString('service2', $around->statement);
    }

    public function testAroundWithServiceTags(): void
    {
        $around = new Around(serviceTags: ['tag1', 'tag2']);
        $this->assertNotNull($around->statement);
        $this->assertStringContainsString('tag1', $around->statement);
        $this->assertStringContainsString('tag2', $around->statement);
    }

    public function testInitializeAdvice(): void
    {
        $around = new Around();

        // Test that initializeAdvice is called during construction
        // Since the constructor already calls initializeAdvice(), we can test the behavior indirectly
        $this->assertNull($around->statement);

        // Test with classAttribute
        $around = new Around(classAttribute: 'TestAttribute');
        $this->assertEquals("count(class.getAttributes('TestAttribute')) > 0", $around->statement);

        // Test with methodAttribute
        $around = new Around(methodAttribute: 'TestMethod');
        $this->assertEquals("count(method.getAttributes('TestMethod')) > 0", $around->statement);

        // Test with serviceIds
        $around = new Around(serviceIds: ['service1', 'service2']);
        $this->assertEquals("(serviceId in ['service1', 'service2'])", $around->statement);

        // Test with serviceTags
        $around = new Around(serviceTags: ['tag1', 'tag2']);
        $this->assertEquals("('tag1' in serviceTags) || ('tag2' in serviceTags)", $around->statement);

        // Test that the last parameter takes precedence when multiple are provided
        $around = new Around(
            classAttribute: 'ClassAttr',
            parentClasses: ['Parent1']
        );
        $this->assertEquals("!class.isFinal() && (('Parent1' in parentClasses))", $around->statement);
    }
}
