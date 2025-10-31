<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;

/**
 * @internal
 */
#[CoversClass(AfterThrowing::class)]
final class AfterThrowingTest extends TestCase
{
    public function testConstructWithStatement(): void
    {
        $attribute = new AfterThrowing(statement: 'method.getName() == "throwError"');

        $this->assertEquals('method.getName() == "throwError"', $attribute->statement);
        $this->assertNull($attribute->classAttribute);
        $this->assertNull($attribute->methodAttribute);
        $this->assertNull($attribute->serviceIds);
        $this->assertNull($attribute->serviceTags);
        $this->assertNull($attribute->parentClasses);
    }

    public function testConstructWithClassAttribute(): void
    {
        $attribute = new AfterThrowing(classAttribute: 'ErrorHandler');

        $this->assertEquals("count(class.getAttributes('ErrorHandler')) > 0", $attribute->statement);
        $this->assertEquals('ErrorHandler', $attribute->classAttribute);
    }

    public function testConstructWithMethodAttribute(): void
    {
        $attribute = new AfterThrowing(methodAttribute: 'HandleError');

        $this->assertEquals("count(method.getAttributes('HandleError')) > 0", $attribute->statement);
        $this->assertEquals('HandleError', $attribute->methodAttribute);
    }

    public function testConstructWithServiceIds(): void
    {
        $serviceIds = ['error.handler', 'exception.listener'];
        $attribute = new AfterThrowing(serviceIds: $serviceIds);

        $this->assertEquals("(serviceId in ['error.handler', 'exception.listener'])", $attribute->statement);
        $this->assertEquals($serviceIds, $attribute->serviceIds);
    }

    public function testConstructWithServiceTags(): void
    {
        $serviceTags = ['error_handler', 'exception_listener'];
        $attribute = new AfterThrowing(serviceTags: $serviceTags);

        $this->assertEquals("('error_handler' in serviceTags) || ('exception_listener' in serviceTags)", $attribute->statement);
        $this->assertEquals($serviceTags, $attribute->serviceTags);
    }

    public function testConstructWithParentClasses(): void
    {
        $parentClasses = ['BaseErrorHandler', 'AbstractExceptionHandler'];
        $attribute = new AfterThrowing(parentClasses: $parentClasses);

        $this->assertEquals("!class.isFinal() && (('BaseErrorHandler' in parentClasses) || ('AbstractExceptionHandler' in parentClasses))", $attribute->statement);
        $this->assertEquals($parentClasses, $attribute->parentClasses);
    }

    public function testConstructWithCombinedLogic(): void
    {
        $attribute = new AfterThrowing(
            statement: '(method.getName() == "process" or method.getName() == "handle") and serviceId != "test.service"'
        );

        $this->assertEquals('(method.getName() == "process" or method.getName() == "handle") and serviceId != "test.service"', $attribute->statement);
    }

    public function testAttributeTargets(): void
    {
        $reflection = new \ReflectionClass(AfterThrowing::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);
        $attributeInstance = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE, $attributeInstance->flags);
    }

    public function testInitializeAdvice(): void
    {
        $attribute = new AfterThrowing();

        // Test that initializeAdvice is called during construction
        // Since the constructor already calls initializeAdvice(), we can test the behavior indirectly
        $this->assertNull($attribute->statement);

        // Test with classAttribute
        $attribute = new AfterThrowing(classAttribute: 'TestAttribute');
        $this->assertEquals("count(class.getAttributes('TestAttribute')) > 0", $attribute->statement);

        // Test with methodAttribute
        $attribute = new AfterThrowing(methodAttribute: 'TestMethod');
        $this->assertEquals("count(method.getAttributes('TestMethod')) > 0", $attribute->statement);

        // Test with serviceIds
        $attribute = new AfterThrowing(serviceIds: ['service1', 'service2']);
        $this->assertEquals("(serviceId in ['service1', 'service2'])", $attribute->statement);

        // Test with serviceTags
        $attribute = new AfterThrowing(serviceTags: ['tag1', 'tag2']);
        $this->assertEquals("('tag1' in serviceTags) || ('tag2' in serviceTags)", $attribute->statement);

        // Test that the last parameter takes precedence when multiple are provided
        $attribute = new AfterThrowing(
            classAttribute: 'ClassAttr',
            parentClasses: ['Parent1']
        );
        $this->assertEquals("!class.isFinal() && (('Parent1' in parentClasses))", $attribute->statement);
    }
}
