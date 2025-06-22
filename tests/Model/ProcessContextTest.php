<?php

namespace Tourze\Symfony\Aop\Tests\Model;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Model\ProcessContext;

class ProcessContextTest extends TestCase
{
    public function testGetPid(): void
    {
        $context = new ProcessContext(12345);
        $this->assertEquals(12345, $context->getPid());
    }
    
    public function testInstanceCreatesNewInstance(): void
    {
        $context = ProcessContext::instance(1000);

        $this->assertInstanceOf(ProcessContext::class, $context);
        $this->assertEquals(1000, $context->getPid());
    }
    
    public function testInstanceReturnsSameInstanceForSamePid(): void
    {
        $context1 = ProcessContext::instance(2000);
        $context2 = ProcessContext::instance(2000);

        $this->assertSame($context1, $context2);
    }
    
    public function testInstanceReturnsDifferentInstancesForDifferentPids(): void
    {
        $context1 = ProcessContext::instance(3000);
        $context2 = ProcessContext::instance(3001);

        $this->assertNotSame($context1, $context2);
        $this->assertEquals(3000, $context1->getPid());
        $this->assertEquals(3001, $context2->getPid());
    }
    
    public function testMultipleInstancesAreManaged(): void
    {
        $contexts = [];

        // Create multiple instances
        for ($i = 5000; $i < 5010; $i++) {
            $contexts[$i] = ProcessContext::instance($i);
        }

        // Verify each instance
        foreach ($contexts as $pid => $context) {
            $this->assertEquals($pid, $context->getPid());
            // Verify it's the same instance when retrieved again
            $this->assertSame($context, ProcessContext::instance($pid));
        }
    }
    
    public function testNegativePid(): void
    {
        $context = ProcessContext::instance(-1);
        $this->assertEquals(-1, $context->getPid());
    }
    
    public function testZeroPid(): void
    {
        $context = ProcessContext::instance(0);
        $this->assertEquals(0, $context->getPid());
    }
    
    public function testLargePid(): void
    {
        $largePid = PHP_INT_MAX;
        $context = ProcessContext::instance($largePid);
        $this->assertEquals($largePid, $context->getPid());
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        // Clear instances between tests using reflection
        $reflection = new \ReflectionClass(ProcessContext::class);
        $property = $reflection->getProperty('instances');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}