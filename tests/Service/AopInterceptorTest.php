<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Service\AopInterceptor;
use Tourze\Symfony\Aop\Tests\Service\Exception\TestException;

/**
 * @internal
 */
#[CoversClass(AopInterceptor::class)]
#[RunTestsInSeparateProcesses] final class AopInterceptorTest extends AbstractIntegrationTestCase
{
    private AopInterceptor $interceptor;

    protected function onSetUp(): void
    {
        $this->interceptor = self::getService(AopInterceptor::class);
    }

    public function testAddAttributeFunction(): void
    {
        $aspectService = function () {
            return new class {
                public function beforeAdvice(JoinPoint $joinPoint): void
                {
                    $joinPoint->setReturnValue('intercepted');
                }
            };
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            Before::class,
            $aspectService,
            'beforeAdvice'
        );

        // 测试拦截器能正确添加和存储属性函数
        $reflection = new \ReflectionClass($this->interceptor);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue($this->interceptor);

        $this->assertArrayHasKey('testMethod', $attributes);
        $this->assertArrayHasKey(Before::class, $attributes['testMethod']);
        $this->assertCount(1, $attributes['testMethod'][Before::class]);
    }

    public function testInvokeWithoutAttributes(): void
    {
        $proxy = new class {};
        $instance = new class {
            public function testMethod(): string
            {
                return 'original';
            }
        };
        $returnEarly = false;

        $result = $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertFalse($returnEarly);
        $this->assertNull($result);
    }

    public function testInvokeWithBeforeAdvice(): void
    {
        $aspectService = function () {
            return new class {
                public function beforeAdvice(JoinPoint $joinPoint): void
                {
                    $joinPoint->setReturnValue('intercepted');
                    $joinPoint->setReturnEarly(true);
                }
            };
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            Before::class,
            $aspectService,
            'beforeAdvice'
        );

        $proxy = new class {};
        $instance = new class {
            public function testMethod(): string
            {
                return 'original';
            }
        };
        $returnEarly = false;

        $result = $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertTrue($returnEarly);
        $this->assertEquals('intercepted', $result);
    }

    public function testInvokeWithAfterReturningAdvice(): void
    {
        $aspectService = function () {
            return new class {
                public function afterReturningAdvice(JoinPoint $joinPoint): void
                {
                    $joinPoint->setReturnValue('modified');
                }
            };
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            AfterReturning::class,
            $aspectService,
            'afterReturningAdvice'
        );

        $proxy = new class {};
        $instance = new class {
            public function testMethod(): string
            {
                return 'original';
            }
        };
        $returnEarly = false;

        $result = $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertTrue($returnEarly);
        $this->assertEquals('original', $result); // AfterReturning 不修改返回值
    }

    public function testInvokeWithAfterThrowingAdvice(): void
    {
        $aspectService = function () {
            return new class {
                public function afterThrowingAdvice(JoinPoint $joinPoint): void
                {
                    $joinPoint->setReturnValue('handled');
                    $joinPoint->setReturnEarly(true);
                }
            };
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            AfterThrowing::class,
            $aspectService,
            'afterThrowingAdvice'
        );

        $proxy = new class {};
        $instance = new class {
            public function testMethod(): string
            {
                throw new TestException('Test exception');
            }
        };
        $returnEarly = false;

        $result = $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertTrue($returnEarly);
        $this->assertEquals('handled', $result);
    }

    public function testInvokeWithAfterAdvice(): void
    {
        $logger = new class {
            /** @var array<string> */
            private array $log = [];

            public function log(string $message): void
            {
                $this->log[] = $message;
            }

            /** @return array<string> */
            public function getLog(): array
            {
                return $this->log;
            }
        };

        $aspectService = function () use ($logger) {
            return new class($logger) {
                private object $logger;

                public function __construct(object $logger)
                {
                    $this->logger = $logger;
                }

                public function afterAdvice(JoinPoint $joinPoint): void
                {
                    // @phpstan-ignore-next-line
                    $this->logger->log('after');
                }

                /** @return array<string> */
                public function getLog(): array
                {
                    // @phpstan-ignore-next-line
                    return $this->logger->getLog();
                }
            };
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            After::class,
            $aspectService,
            'afterAdvice'
        );

        $proxy = new class {};
        $instance = new class {
            public function testMethod(): string
            {
                return 'original';
            }
        };
        $returnEarly = false;

        $result = $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertTrue($returnEarly);
        $this->assertEquals('original', $result);
        $this->assertEquals(['after'], $logger->getLog());
    }

    public function testRedisMethodParamsFix(): void
    {
        $aspectService = function () {
            return new class {};
        };

        $this->interceptor->addAttributeFunction(
            'info',
            Before::class,
            $aspectService,
            'beforeAdvice'
        );

        $redis = $this->createMock(\Redis::class);
        $proxy = new class {};
        $returnEarly = false;

        // 测试 Redis info 方法的参数修复
        $params = ['sections' => ['memory', 'stats']];
        $this->interceptor->__invoke($proxy, $redis, 'info', $params, $returnEarly);

        $this->assertTrue($returnEarly);
    }

    public function testGlobalSequenceIdIncrement(): void
    {
        $reflection = new \ReflectionClass(AopInterceptor::class);
        $property = $reflection->getProperty('globalSequenceId');
        $property->setAccessible(true);
        $initialValue = $property->getValue();

        $aspectService = function () {
            return new class {};
        };

        $this->interceptor->addAttributeFunction(
            'testMethod',
            Before::class,
            $aspectService,
            'beforeAdvice'
        );

        $proxy = new class {};
        $instance = new class {
            public function testMethod(): void
            {
                // Do nothing
            }
        };
        $returnEarly = false;

        $this->interceptor->__invoke($proxy, $instance, 'testMethod', [], $returnEarly);

        $this->assertEquals($initialValue + 1, $property->getValue());
    }
}
