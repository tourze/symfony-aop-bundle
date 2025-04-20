<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\After;

class AfterTest extends TestCase
{
    public function testInheritanceFromAdvice(): void
    {
        $after = new After('class.getName() === "TestClass"');
        $this->assertEquals('class.getName() === "TestClass"', $after->statement);
    }

    public function testConstructorWithClassAttribute(): void
    {
        $after = new After(classAttribute: 'App\\Attribute\\TestAttribute');
        $actualStatement = $after->statement;
        $this->assertStringContainsString('count(class.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithMethodAttribute(): void
    {
        $after = new After(methodAttribute: 'App\\Attribute\\TestAttribute');
        $actualStatement = $after->statement;
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
        $after = new After(parentClasses: ['App\\BaseClass']);
        $actualStatement = $after->statement;
        $this->assertStringContainsString('!class.isFinal()', $actualStatement);
        $this->assertStringContainsString('BaseClass', $actualStatement);
    }
}
