<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Before;

class BeforeTest extends TestCase
{
    public function testInheritanceFromAdvice(): void
    {
        $before = new Before('class.getName() === "TestClass"');
        $this->assertEquals('class.getName() === "TestClass"', $before->statement);
    }

    public function testConstructorWithClassAttribute(): void
    {
        $before = new Before(classAttribute: 'App\\Attribute\\TestAttribute');
        $actualStatement = $before->statement;
        $this->assertStringContainsString('count(class.getAttributes(', $actualStatement);
        $this->assertStringContainsString('TestAttribute', $actualStatement);
    }

    public function testConstructorWithMethodAttribute(): void
    {
        $before = new Before(methodAttribute: 'App\\Attribute\\TestAttribute');
        $actualStatement = $before->statement;
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
        $before = new Before(parentClasses: ['App\\BaseClass']);
        $actualStatement = $before->statement;
        $this->assertStringContainsString('!class.isFinal()', $actualStatement);
        $this->assertStringContainsString('BaseClass', $actualStatement);
    }
}
