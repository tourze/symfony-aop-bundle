<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\AfterReturning;

class AfterReturningTest extends TestCase
{
    public function testConstructWithStatement(): void
    {
        $attribute = new AfterReturning(statement: 'method.getName() == "test"');
        
        $this->assertEquals('method.getName() == "test"', $attribute->statement);
        $this->assertNull($attribute->classAttribute);
        $this->assertNull($attribute->methodAttribute);
        $this->assertNull($attribute->serviceIds);
        $this->assertNull($attribute->serviceTags);
        $this->assertNull($attribute->parentClasses);
    }
    
    public function testConstructWithClassAttribute(): void
    {
        $attribute = new AfterReturning(classAttribute: 'TestAttribute');
        
        $this->assertEquals("count(class.getAttributes('TestAttribute')) > 0", $attribute->statement);
        $this->assertEquals('TestAttribute', $attribute->classAttribute);
    }
    
    public function testConstructWithMethodAttribute(): void
    {
        $attribute = new AfterReturning(methodAttribute: 'TestMethod');
        
        $this->assertEquals("count(method.getAttributes('TestMethod')) > 0", $attribute->statement);
        $this->assertEquals('TestMethod', $attribute->methodAttribute);
    }
    
    public function testConstructWithServiceIds(): void
    {
        $serviceIds = ['service1', 'service2'];
        $attribute = new AfterReturning(serviceIds: $serviceIds);
        
        $this->assertEquals("(serviceId in ['service1', 'service2'])", $attribute->statement);
        $this->assertEquals($serviceIds, $attribute->serviceIds);
    }
    
    public function testConstructWithServiceTags(): void
    {
        $serviceTags = ['tag1', 'tag2'];
        $attribute = new AfterReturning(serviceTags: $serviceTags);
        
        $this->assertEquals("('tag1' in serviceTags) || ('tag2' in serviceTags)", $attribute->statement);
        $this->assertEquals($serviceTags, $attribute->serviceTags);
    }
    
    public function testConstructWithParentClasses(): void
    {
        $parentClasses = ['ParentClass1', 'ParentClass2'];
        $attribute = new AfterReturning(parentClasses: $parentClasses);
        
        $this->assertEquals("!class.isFinal() && (('ParentClass1' in parentClasses) || ('ParentClass2' in parentClasses))", $attribute->statement);
        $this->assertEquals($parentClasses, $attribute->parentClasses);
    }
    
    public function testConstructWithMultipleParameters(): void
    {
        // Test priority: later parameters override earlier ones when statement is not provided
        $attribute = new AfterReturning(
            classAttribute: 'ClassAttr',
            methodAttribute: 'MethodAttr',
            serviceIds: ['service1'],
            serviceTags: ['tag1'],
            parentClasses: ['Parent1']
        );
        
        // parentClasses is the last parameter, so it takes precedence
        $this->assertEquals("!class.isFinal() && (('Parent1' in parentClasses))", $attribute->statement);
        $this->assertEquals('ClassAttr', $attribute->classAttribute);
        $this->assertEquals('MethodAttr', $attribute->methodAttribute);
        $this->assertEquals(['service1'], $attribute->serviceIds);
        $this->assertEquals(['tag1'], $attribute->serviceTags);
        $this->assertEquals(['Parent1'], $attribute->parentClasses);
    }
    
    public function testConstructWithExplicitStatement(): void
    {
        $attribute = new AfterReturning(statement: 'custom statement');
        
        // When only statement is provided, it's used as-is
        $this->assertEquals('custom statement', $attribute->statement);
        $this->assertNull($attribute->classAttribute);
        $this->assertNull($attribute->parentClasses);
    }
    
    public function testConstructWithEmptyArrays(): void
    {
        $attribute = new AfterReturning(
            serviceIds: [],
            serviceTags: [],
            parentClasses: []
        );
        
        // Empty arrays generate parentClasses statement
        $this->assertEquals("!class.isFinal() && ()", $attribute->statement);
    }
}