<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Aspect;

class AspectTest extends TestCase
{
    public function testAttributeConstruction(): void
    {
        $attribute = new Aspect();
        
        // Aspect is a marker attribute with no properties
        $this->assertInstanceOf(Aspect::class, $attribute);
    }
    
    public function testAttributeTargets(): void
    {
        $reflection = new \ReflectionClass(Aspect::class);
        $attributes = $reflection->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        $attributeInstance = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_CLASS, $attributeInstance->flags);
    }
    
    public function testUsageOnClass(): void
    {
        // Create a test class with Aspect attribute
        $testClass = new #[Aspect] class {
            public function beforeMethod(): void {}
        };
        
        $reflection = new \ReflectionClass($testClass);
        $attributes = $reflection->getAttributes(Aspect::class);
        
        $this->assertCount(1, $attributes);
        $this->assertInstanceOf(Aspect::class, $attributes[0]->newInstance());
    }
    
    public function testTagName(): void
    {
        $this->assertEquals('aop.aspect', Aspect::TAG_NAME);
    }
    
    public function testAutoconfigureTag(): void
    {
        // Aspect extends from AutoconfigureTag
        $aspect = new Aspect();
        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag::class, $aspect);
    }
}