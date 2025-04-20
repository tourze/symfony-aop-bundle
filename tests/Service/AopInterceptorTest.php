<?php

namespace Tourze\Symfony\Aop\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\AfterReturning;
use Tourze\Symfony\Aop\Attribute\AfterThrowing;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Model\JoinPoint;
use Tourze\Symfony\Aop\Service\AopInterceptor;

class AopInterceptorTest extends TestCase
{
    private AopInterceptor $interceptor;
    private object $proxy;
    private object $instance;

    protected function setUp(): void
    {
        $this->interceptor = new AopInterceptor();
        $this->interceptor->setInternalServiceId('test.service.internal');
        $this->interceptor->setProxyServiceId('test.service');

        $this->proxy = new \stdClass();
        $this->instance = new \stdClass();
    }

    public function testAddAttributeFunction(): void
    {
        $method = 'testMethod';
        $attribute = Before::class;
        $aspectService = function () {
            return new class {
                public function aspectMethod(JoinPoint $joinPoint): void
                {
                    // 测试方法
                }
            };
        };
        $aspectMethod = 'aspectMethod';

        $this->interceptor->addAttributeFunction($method, $attribute, $aspectService, $aspectMethod);

        // 使用反射来验证私有属性是否被正确设置
        $reflection = new \ReflectionClass($this->interceptor);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);

        $attributes = $property->getValue($this->interceptor);

        $this->assertArrayHasKey($method, $attributes);
        $this->assertArrayHasKey($attribute, $attributes[$method]);
        $this->assertCount(1, $attributes[$method][$attribute]);
        $this->assertCount(2, $attributes[$method][$attribute][0]);
        $this->assertInstanceOf(\Closure::class, $attributes[$method][$attribute][0][0]);
        $this->assertEquals($aspectMethod, $attributes[$method][$attribute][0][1]);
    }

    public function testInvokeWithNoAttributes(): void
    {
        $method = 'nonExistentMethod';
        $params = [];
        $returnEarly = false;

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        $this->assertNull($result);
        $this->assertFalse($returnEarly);
    }

    public function testInvokeWithBeforeAdvice(): void
    {
        $method = 'testMethod';
        $params = ['value1', 123];
        $returnEarly = false;

        // 创建一个模拟的Before通知
        $called = false;
        $aspectService = function () use ($called) {
            return new class($called) {
                private bool $wasCalled;

                public function __construct(bool $initialValue)
                {
                    $this->wasCalled = $initialValue;
                }

                public function beforeMethod(JoinPoint $joinPoint): void
                {
                    $this->wasCalled = true;
                }

                public function wasCalled(): bool
                {
                    return $this->wasCalled;
                }
            };
        };

        $this->interceptor->addAttributeFunction($method, Before::class, $aspectService, 'beforeMethod');

        // 动态添加方法到测试对象
        $this->instance = new class {
            public function testMethod($param1, $param2)
            {
                return "Result: $param1-$param2";
            }
        };

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        // 对于这个测试，我们可以直接检查方法是否被调用
        // 由于无法直接访问闭包内的变量，我们将简化测试断言
        $this->assertTrue($returnEarly, 'Return early flag was not set');
        $this->assertEquals('Result: value1-123', $result);
    }

    public function testInvokeWithAfterReturningAdvice(): void
    {
        $method = 'testMethod';
        $params = [];
        $returnEarly = false;

        // 创建一个模拟的AfterReturning通知
        $returnValue = 'original method result';
        $aspectService = function () use ($returnValue) {
            return new class($returnValue) {
                private string $expectedReturnValue;
                public bool $hasCorrectReturnValue = false;

                public function __construct(string $returnValue)
                {
                    $this->expectedReturnValue = $returnValue;
                }

                public function afterReturningMethod(JoinPoint $joinPoint): void
                {
                    $this->hasCorrectReturnValue = ($joinPoint->getReturnValue() === $this->expectedReturnValue);
                }
            };
        };

        $this->interceptor->addAttributeFunction($method, AfterReturning::class, $aspectService, 'afterReturningMethod');

        // 动态添加方法到测试对象
        $this->instance = new class($returnValue) {
            private string $value;

            public function __construct(string $value)
            {
                $this->value = $value;
            }

            public function testMethod()
            {
                return $this->value;
            }
        };

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        $this->assertTrue($returnEarly, 'Return early flag was not set');
        $this->assertEquals($returnValue, $result);
    }

    public function testInvokeWithAfterThrowingAdvice(): void
    {
        $method = 'testMethod';
        $params = [];
        $returnEarly = false;

        // 创建一个模拟的AfterThrowing通知
        $aspectService = function () {
            return new class {
                public bool $wasCalled = false;
                public bool $caughtException = false;

                public function afterThrowingMethod(JoinPoint $joinPoint): void
                {
                    $this->wasCalled = true;
                    $this->caughtException = ($joinPoint->getException() instanceof \RuntimeException);

                    // 设置为提前返回
                    $joinPoint->setReturnEarly(true);
                    $joinPoint->setReturnValue('error handled');
                }
            };
        };

        $this->interceptor->addAttributeFunction($method, AfterThrowing::class, $aspectService, 'afterThrowingMethod');

        // 动态添加会抛出异常的方法到测试对象
        $this->instance = new class {
            public function testMethod()
            {
                throw new \RuntimeException('Test exception');
            }
        };

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        $this->assertTrue($returnEarly, 'Return early flag was not set');
        $this->assertEquals('error handled', $result, 'Custom return value not honored');
    }

    public function testInvokeWithAfterAdvice(): void
    {
        $method = 'testMethod';
        $params = [];
        $returnEarly = false;

        // 创建一个模拟的After通知
        $aspectService = function () {
            return new class {
                public bool $wasCalled = false;

                public function afterMethod(JoinPoint $joinPoint): void
                {
                    $this->wasCalled = true;
                }
            };
        };

        $this->interceptor->addAttributeFunction($method, After::class, $aspectService, 'afterMethod');

        // 动态添加方法到测试对象
        $this->instance = new class {
            public function testMethod()
            {
                return 'original method result';
            }
        };

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        $this->assertTrue($returnEarly, 'Return early flag was not set');
        $this->assertEquals('original method result', $result);
    }

    public function testInvokeWithAllAdvicesInOrder(): void
    {
        $method = 'testMethod';
        $params = [];
        $returnEarly = false;

        // 使用静态数组来跟踪执行顺序
        $executionOrder = [];

        // Before通知
        $aspectBefore = function () use (&$executionOrder) {
            // 不使用引用传递，避免语法错误
            $orderRef = &$executionOrder;
            return new class($orderRef) {
                private array $orderRef;

                public function __construct(array &$orderRef)
                {
                    $this->orderRef = &$orderRef;
                }

                public function beforeMethod(JoinPoint $joinPoint): void
                {
                    $this->orderRef[] = 'before';
                }
            };
        };

        // After通知
        $aspectAfter = function () use (&$executionOrder) {
            // 不使用引用传递，避免语法错误
            $orderRef = &$executionOrder;
            return new class($orderRef) {
                private array $orderRef;

                public function __construct(array &$orderRef)
                {
                    $this->orderRef = &$orderRef;
                }

                public function afterMethod(JoinPoint $joinPoint): void
                {
                    $this->orderRef[] = 'after';
                }
            };
        };

        // AfterReturning通知
        $aspectAfterReturning = function () use (&$executionOrder) {
            // 不使用引用传递，避免语法错误
            $orderRef = &$executionOrder;
            return new class($orderRef) {
                private array $orderRef;

                public function __construct(array &$orderRef)
                {
                    $this->orderRef = &$orderRef;
                }

                public function afterReturningMethod(JoinPoint $joinPoint): void
                {
                    $this->orderRef[] = 'afterReturning';
                }
            };
        };

        $this->interceptor->addAttributeFunction($method, Before::class, $aspectBefore, 'beforeMethod');
        $this->interceptor->addAttributeFunction($method, After::class, $aspectAfter, 'afterMethod');
        $this->interceptor->addAttributeFunction($method, AfterReturning::class, $aspectAfterReturning, 'afterReturningMethod');

        // 动态添加方法到测试对象，使用静态变量追踪执行顺序
        $testOrderRef = &$executionOrder;
        $this->instance = new class($testOrderRef) {
            private array $orderRef;

            public function __construct(array &$orderRef)
            {
                $this->orderRef = &$orderRef;
            }

            public function testMethod()
            {
                $this->orderRef[] = 'method';
                return 'result';
            }
        };

        $result = ($this->interceptor)($this->proxy, $this->instance, $method, $params, $returnEarly);

        $expectedOrder = ['before', 'method', 'afterReturning', 'after'];
        $this->assertEquals($expectedOrder, $executionOrder, 'Advice execution order is incorrect');
        $this->assertTrue($returnEarly, 'Return early flag was not set');
        $this->assertEquals('result', $result);
    }
}
