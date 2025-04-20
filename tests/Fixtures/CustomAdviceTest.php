<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\Advice;

class CustomAdviceTest extends TestCase
{
    public function testCustomAdviceExtendsAdvice(): void
    {
        $customAdvice = new CustomAdvice('class.getName() === "TestClass"');
        $this->assertInstanceOf(Advice::class, $customAdvice);
        $this->assertEquals('class.getName() === "TestClass"', $customAdvice->statement);
    }

    public function testCustomAdviceWithServiceIds(): void
    {
        $customAdvice = new CustomAdvice(serviceIds: ['app.service']);
        $this->assertEquals(['app.service'], $customAdvice->serviceIds);

        $expectedStatement = "(serviceId in ['app.service'])";
        $this->assertEquals($expectedStatement, $customAdvice->statement);
    }

    public function testCustomAdviceAttributeIsRepeatable(): void
    {
        $reflectionClass = new \ReflectionClass(CustomAdvice::class);
        $attributes = $reflectionClass->getAttributes();

        $found = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === \Attribute::class) {
                $instance = $attribute->newInstance();
                if ($instance->flags & \Attribute::IS_REPEATABLE) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, 'CustomAdvice attribute should be repeatable');
    }
}
