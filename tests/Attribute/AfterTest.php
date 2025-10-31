<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\After;

/**
 * @internal
 */
#[CoversClass(After::class)]
final class AfterTest extends TestCase
{
    public function testInheritanceFromAdvice(): void
    {
        $after = new After('class.getName() === "TestClass"');
        $this->assertEquals('class.getName() === "TestClass"', $after->statement);
    }

    public function testConstructorWithClassAttribute(): void
    {
        $after = new After(classAttribute: 'App\Attribute\TestAttribute');
        $actualStatement = $after->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('count(class.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithMethodAttribute(): void
    {
        $after = new After(methodAttribute: 'App\Attribute\TestAttribute');
        $actualStatement = $after->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('count(method.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithServiceIds(): void
    {
        $after = new After(serviceIds: ['app.service']);
        $expectedStatement = "(serviceId in ['app.service'])";
        $this->assertEquals($expectedStatement, $after->statement);
    }

    public function testConstructorWithServiceTags(): void
    {
        $after = new After(serviceTags: ['app.tag']);
        $expectedStatement = "('app.tag' in serviceTags)";
        $this->assertEquals($expectedStatement, $after->statement);
    }

    public function testConstructorWithParentClasses(): void
    {
        $after = new After(parentClasses: ['App\BaseClass']);
        $actualStatement = $after->statement;
        $this->assertNotNull($actualStatement);
        $this->assertStringContainsString('!class.isFinal()', $actualStatement);
        $this->assertStringContainsString('BaseClass', $actualStatement);
    }

    public function testInitializeAdvice(): void
    {
        $after = new After();

        // Test that initializeAdvice is called during construction
        // Since the constructor already calls initializeAdvice(), we can test the behavior indirectly
        $this->assertNull($after->statement);

        // Test with classAttribute
        $after = new After(classAttribute: 'TestAttribute');
        $this->assertEquals("count(class.getAttributes('TestAttribute')) > 0", $after->statement);

        // Test with methodAttribute
        $after = new After(methodAttribute: 'TestMethod');
        $this->assertEquals("count(method.getAttributes('TestMethod')) > 0", $after->statement);

        // Test with serviceIds
        $after = new After(serviceIds: ['service1', 'service2']);
        $this->assertEquals("(serviceId in ['service1', 'service2'])", $after->statement);

        // Test with serviceTags
        $after = new After(serviceTags: ['tag1', 'tag2']);
        $this->assertEquals("('tag1' in serviceTags) || ('tag2' in serviceTags)", $after->statement);

        // Test that the last parameter takes precedence when multiple are provided
        $after = new After(
            classAttribute: 'ClassAttr',
            parentClasses: ['Parent1']
        );
        $this->assertEquals("!class.isFinal() && (('Parent1' in parentClasses))", $after->statement);
    }
}
