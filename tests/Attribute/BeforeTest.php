<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Before;

/**
 * @internal
 */
#[CoversClass(Before::class)]
final class BeforeTest extends TestCase
{
    public function testInheritanceFromAdvice(): void
    {
        $before = new Before('class.getName() === "TestClass"');
        $this->assertEquals('class.getName() === "TestClass"', $before->statement);
    }

    public function testConstructorWithClassAttribute(): void
    {
        $before = new Before(classAttribute: 'App\Attribute\TestAttribute');
        $actualStatement = $before->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('count(class.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithMethodAttribute(): void
    {
        $before = new Before(methodAttribute: 'App\Attribute\TestAttribute');
        $actualStatement = $before->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('count(method.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithServiceIds(): void
    {
        $before = new Before(serviceIds: ['app.service']);
        $expectedStatement = "(serviceId in ['app.service'])";
        $this->assertEquals($expectedStatement, $before->statement);
    }

    public function testConstructorWithServiceTags(): void
    {
        $before = new Before(serviceTags: ['app.tag']);
        $expectedStatement = "('app.tag' in serviceTags)";
        $this->assertEquals($expectedStatement, $before->statement);
    }

    public function testConstructorWithParentClasses(): void
    {
        $before = new Before(parentClasses: ['App\BaseClass']);
        $actualStatement = $before->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('!class.isFinal()', $actualStatement);
        $this->assertStringContainsString('BaseClass', $actualStatement);
    }

    public function testInitializeAdvice(): void
    {
        $before = new Before();

        // Test that initializeAdvice is called during construction
        // Since the constructor already calls initializeAdvice(), we can test the behavior indirectly
        $this->assertNull($before->statement);

        // Test with classAttribute
        $before = new Before(classAttribute: 'TestAttribute');
        $this->assertEquals("count(class.getAttributes('TestAttribute')) > 0", $before->statement);

        // Test with methodAttribute
        $before = new Before(methodAttribute: 'TestMethod');
        $this->assertEquals("count(method.getAttributes('TestMethod')) > 0", $before->statement);

        // Test with serviceIds
        $before = new Before(serviceIds: ['service1', 'service2']);
        $this->assertEquals("(serviceId in ['service1', 'service2'])", $before->statement);

        // Test with serviceTags
        $before = new Before(serviceTags: ['tag1', 'tag2']);
        $this->assertEquals("('tag1' in serviceTags) || ('tag2' in serviceTags)", $before->statement);

        // Test that the last parameter takes precedence when multiple are provided
        $before = new Before(
            classAttribute: 'ClassAttr',
            parentClasses: ['Parent1']
        );
        $this->assertEquals("!class.isFinal() && (('Parent1' in parentClasses))", $before->statement);
    }
}
