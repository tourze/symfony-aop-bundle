<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Service\InstanceService;

// Test fixtures
class TestFactory
{
    public static function createStaticInstance(string $arg1, int $arg2): TestProduct
    {
        return new TestProduct($arg1, $arg2);
    }
    
    public function createInstance(string $arg1, int $arg2): TestProduct
    {
        return new TestProduct($arg1, $arg2);
    }
}

class TestProduct
{
    public function __construct(
        public string $arg1,
        public int $arg2
    ) {}
}

class InstanceServiceTest extends TestCase
{
    private InstanceService $service;
    
    public function testCreateWithStaticFactoryMethod(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getInstance')->willReturn(new \stdClass());
        $joinPoint->method('getFactoryInstance')->willReturn(TestFactory::class);
        $joinPoint->method('getFactoryMethod')->willReturn('createStaticInstance');
        $joinPoint->method('getFactoryArguments')->willReturn(['test', 42]);

        $result = $this->service->create($joinPoint);

        $this->assertInstanceOf(TestProduct::class, $result);
        $this->assertEquals('test', $result->arg1);
        $this->assertEquals(42, $result->arg2);
    }
    
    public function testCreateWithObjectFactoryMethod(): void
    {
        $factory = new TestFactory();
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getInstance')->willReturn(new \stdClass());
        $joinPoint->method('getFactoryInstance')->willReturn($factory);
        $joinPoint->method('getFactoryMethod')->willReturn('createInstance');
        $joinPoint->method('getFactoryArguments')->willReturn(['object', 123]);

        $result = $this->service->create($joinPoint);

        $this->assertInstanceOf(TestProduct::class, $result);
        $this->assertEquals('object', $result->arg1);
        $this->assertEquals(123, $result->arg2);
    }
    
    public function testCreateWithClone(): void
    {
        $originalInstance = new TestProduct('original', 999);
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getInstance')->willReturn($originalInstance);
        $joinPoint->method('getFactoryInstance')->willReturn(null);
        $joinPoint->method('getFactoryMethod')->willReturn(null);
        $joinPoint->method('getFactoryArguments')->willReturn([]);

        $result = $this->service->create($joinPoint);

        $this->assertInstanceOf(TestProduct::class, $result);
        $this->assertNotSame($originalInstance, $result);
        $this->assertEquals('original', $result->arg1);
        $this->assertEquals(999, $result->arg2);
    }
    
    public function testCreateWithEmptyFactoryArguments(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getInstance')->willReturn(new \stdClass());
        $joinPoint->method('getFactoryInstance')->willReturn(TestFactoryNoArgs::class);
        $joinPoint->method('getFactoryMethod')->willReturn('create');
        $joinPoint->method('getFactoryArguments')->willReturn([]);

        $result = $this->service->create($joinPoint);

        $this->assertInstanceOf(TestProductNoArgs::class, $result);
    }
    
    public function testCreateWithMultipleArguments(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getInstance')->willReturn(new \stdClass());
        $joinPoint->method('getFactoryInstance')->willReturn(TestFactoryMultiArgs::class);
        $joinPoint->method('getFactoryMethod')->willReturn('create');
        $joinPoint->method('getFactoryArguments')->willReturn(['a', 'b', 'c', 'd', 'e']);

        $result = $this->service->create($joinPoint);

        $this->assertInstanceOf(TestProductMultiArgs::class, $result);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $result->args);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InstanceService();
    }
}

// Additional test fixtures
class TestFactoryNoArgs
{
    public static function create(): TestProductNoArgs
    {
        return new TestProductNoArgs();
    }
}

class TestProductNoArgs
{
}

class TestFactoryMultiArgs
{
    public static function create(...$args): TestProductMultiArgs
    {
        return new TestProductMultiArgs($args);
    }
}

class TestProductMultiArgs
{
    public function __construct(
        public array $args
    ) {}
}