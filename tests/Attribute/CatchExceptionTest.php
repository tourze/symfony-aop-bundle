<?php

namespace Tourze\Symfony\Aop\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\CatchException;

class CatchExceptionTest extends TestCase
{
    public function testAttributeConstruction(): void
    {
        $attribute = new CatchException();
        
        // CatchException is a marker attribute with no properties
        $this->assertInstanceOf(CatchException::class, $attribute);
    }
    
    public function testAttributeTargets(): void
    {
        $reflection = new \ReflectionClass(CatchException::class);
        $attributes = $reflection->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        $attributeInstance = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_METHOD, $attributeInstance->flags);
    }
    
    public function testUsageOnMethod(): void
    {
        // Create a test class with CatchException attribute on method
        $testClass = new class {
            #[CatchException]
            public function method(): void {}
        };
        
        $reflection = new \ReflectionMethod($testClass, 'method');
        $attributes = $reflection->getAttributes(CatchException::class);
        
        $this->assertCount(1, $attributes);
        $this->assertInstanceOf(CatchException::class, $attributes[0]->newInstance());
    }
    
    public function testExtendedFromAutoconfigureTag(): void
    {
        $attribute = new CatchException();
        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag::class, $attribute);
    }
}