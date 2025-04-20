<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Advice;

class AdviceTest extends TestCase
{
    public function testConstructorWithoutParameters(): void
    {
        $advice = new Advice();
        $this->assertNull($advice->statement);
        $this->assertNull($advice->classAttribute);
        $this->assertNull($advice->methodAttribute);
        $this->assertNull($advice->serviceIds);
        $this->assertNull($advice->serviceTags);
        $this->assertNull($advice->parentClasses);
    }

    public function testConstructorWithStatement(): void
    {
        $statement = 'class.getName() === "App\\\\Service\\\\UserService"';
        $advice = new Advice($statement);
        $this->assertEquals($statement, $advice->statement);
    }

    public function testConstructorWithClassAttribute(): void
    {
        $classAttribute = 'App\\Attribute\\MyAttribute';
        $advice = new Advice(classAttribute: $classAttribute);
        $this->assertEquals($classAttribute, $advice->classAttribute);
        $actualStatement = $advice->statement;
        $this->assertStringContainsString('count(class.getAttributes(', $actualStatement);
        $this->assertStringContainsString('MyAttribute', $actualStatement);
    }

    public function testConstructorWithMethodAttribute(): void
    {
        $methodAttribute = 'App\\Attribute\\MyAttribute';
        $advice = new Advice(methodAttribute: $methodAttribute);
        $this->assertEquals($methodAttribute, $advice->methodAttribute);
        $actualStatement = $advice->statement;
        $this->assertStringContainsString('count(method.getAttributes(', $actualStatement);
        $this->assertStringContainsString('MyAttribute', $actualStatement);
    }

    public function testConstructorWithServiceIds(): void
    {
        $serviceIds = ['app.service1', 'app.service2'];
        $advice = new Advice(serviceIds: $serviceIds);
        $this->assertEquals($serviceIds, $advice->serviceIds);
        $expectedStatement = "(serviceId in ['app.service1', 'app.service2'])";
        $this->assertEquals($expectedStatement, $advice->statement);
    }

    public function testConstructorWithServiceIdsPrefixWildcard(): void
    {
        $serviceIds = ['*service'];
        $advice = new Advice(serviceIds: $serviceIds);
        $this->assertEquals($serviceIds, $advice->serviceIds);
        $expectedStatement = "(serviceId in ['']) || (serviceId ends with 'service')";
        $this->assertEquals($expectedStatement, $advice->statement);
    }

    public function testConstructorWithServiceIdsSuffixWildcard(): void
    {
        $serviceIds = ['app.*'];
        $advice = new Advice(serviceIds: $serviceIds);
        $this->assertEquals($serviceIds, $advice->serviceIds);
        $expectedStatement = "(serviceId in ['']) || (serviceId starts with 'app.')";
        $this->assertEquals($expectedStatement, $advice->statement);
    }

    public function testConstructorWithServiceTags(): void
    {
        $serviceTags = ['app.loggable', 'app.cacheable'];
        $advice = new Advice(serviceTags: $serviceTags);
        $this->assertEquals($serviceTags, $advice->serviceTags);
        $expectedStatement = "('app.loggable' in serviceTags) || ('app.cacheable' in serviceTags)";
        $this->assertEquals($expectedStatement, $advice->statement);
    }

    public function testConstructorWithParentClasses(): void
    {
        $parentClasses = ['App\\Base\\BaseRepository'];
        $advice = new Advice(parentClasses: $parentClasses);
        $this->assertEquals($parentClasses, $advice->parentClasses);
        $actualStatement = $advice->statement;
        $this->assertStringContainsString('!class.isFinal()', $actualStatement);
        $this->assertStringContainsString('BaseRepository', $actualStatement);
    }
}
