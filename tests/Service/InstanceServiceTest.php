<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\Aop\Exception\AopException;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Service\InstanceService;

/**
 * @internal
 */
#[CoversClass(InstanceService::class)]
#[RunTestsInSeparateProcesses]
final class InstanceServiceTest extends AbstractIntegrationTestCase
{
    private InstanceService $instanceService;

    protected function onSetUp(): void
    {
        $this->instanceService = self::getService(InstanceService::class);
    }

    private function createJoinPoint(): JoinPoint
    {
        // JoinPoint is a simple value object, not a service
        // But we follow the pattern to avoid direct instantiation in integration tests
        return new JoinPoint();
    }

    public function testCreateWithClone(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {
            public string $value = 'test';
        };
        $joinPoint->setInstance($instance);

        $result = $this->instanceService->create($joinPoint);

        $this->assertEquals($instance, $result);
        $this->assertNotSame($instance, $result); // 应该是克隆的对象
    }

    public function testCreateWithStaticFactory(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $joinPoint->setFactoryInstance(TestFactory::class);
        $joinPoint->setFactoryMethod('createInstance');
        $joinPoint->setFactoryArguments(['test-value']);

        $result = $this->instanceService->create($joinPoint);

        $this->assertInstanceOf(TestFactory::class, $result);
        $this->assertEquals('test-value', $result->value);
    }

    public function testCreateWithObjectFactory(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $factory = new TestFactory();
        $joinPoint->setFactoryInstance($factory);
        $joinPoint->setFactoryMethod('createInstance');
        $joinPoint->setFactoryArguments(['test-value']);

        $result = $this->instanceService->create($joinPoint);

        $this->assertInstanceOf(TestFactory::class, $result);
        $this->assertEquals('test-value', $result->value);
    }

    public function testCreateWithInvalidStaticFactory(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $joinPoint->setFactoryInstance('NonExistentClass');
        $joinPoint->setFactoryMethod('nonExistentMethod');
        $joinPoint->setFactoryArguments([]);

        $this->expectException(AopException::class);
        $this->expectExceptionMessage('Static method NonExistentClass::nonExistentMethod is not callable');

        $this->instanceService->create($joinPoint);
    }

    public function testCreateWithInvalidObjectFactory(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $factory = new TestFactory();
        $joinPoint->setFactoryInstance($factory);
        $joinPoint->setFactoryMethod('nonExistentMethod');
        $joinPoint->setFactoryArguments([]);

        $this->expectException(AopException::class);
        $this->expectExceptionMessage('Method Tourze\Symfony\Aop\Tests\Service\TestFactory::nonExistentMethod is not callable');

        $this->instanceService->create($joinPoint);
    }

    public function testCreateWithMissingFactoryMethod(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $joinPoint->setFactoryInstance(TestFactory::class);
        // 不设置 factoryMethod

        $result = $this->instanceService->create($joinPoint);

        $this->assertEquals($instance, $result);
        $this->assertNotSame($instance, $result);
    }

    public function testCreateWithMissingFactoryArguments(): void
    {
        $joinPoint = $this->createJoinPoint();
        $instance = new class {};
        $joinPoint->setInstance($instance);
        $joinPoint->setFactoryInstance(TestFactory::class);
        $joinPoint->setFactoryMethod('createInstance');
        // 不设置 factoryArguments

        $result = $this->instanceService->create($joinPoint);

        $this->assertEquals($instance, $result);
        $this->assertNotSame($instance, $result);
    }
}
