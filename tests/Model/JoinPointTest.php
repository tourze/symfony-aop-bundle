<?php

namespace Tourze\Symfony\Aop\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Model\JoinPoint;

/**
 * @internal
 */
#[CoversClass(JoinPoint::class)]
final class JoinPointTest extends TestCase
{
    private JoinPoint $joinPoint;

    private \stdClass $proxy;

    private \stdClass $instance;

    protected function setUp(): void
    {
        $this->joinPoint = new JoinPoint();
        $this->proxy = new \stdClass();
        $this->instance = new \stdClass();
    }

    public function testProxy(): void
    {
        $this->joinPoint->setProxy($this->proxy);
        $this->assertSame($this->proxy, $this->joinPoint->getProxy());
    }

    public function testInstance(): void
    {
        $this->joinPoint->setInstance($this->instance);
        $this->assertSame($this->instance, $this->joinPoint->getInstance());
    }

    public function testMethod(): void
    {
        $method = 'testMethod';
        $this->joinPoint->setMethod($method);
        $this->assertSame($method, $this->joinPoint->getMethod());
    }

    public function testParams(): void
    {
        $params = ['param1' => 'value1', 'param2' => 'value2'];
        $this->joinPoint->setParams($params);
        $this->assertSame($params, $this->joinPoint->getParams());
    }

    public function testReturnEarly(): void
    {
        $this->assertFalse($this->joinPoint->isReturnEarly());
        $this->joinPoint->setReturnEarly(true);
        $this->assertTrue($this->joinPoint->isReturnEarly());
    }

    public function testReturnValue(): void
    {
        $returnValue = 'returnValue';
        $this->joinPoint->setReturnValue($returnValue);
        $this->assertSame($returnValue, $this->joinPoint->getReturnValue());
    }

    public function testSequenceId(): void
    {
        $sequenceId = 123;
        $this->joinPoint->setSequenceId($sequenceId);
        $this->assertSame($sequenceId, $this->joinPoint->getSequenceId());
    }

    public function testException(): void
    {
        $exception = new \Exception('Test exception');
        $this->joinPoint->setException($exception);
        $this->assertSame($exception, $this->joinPoint->getException());
    }

    public function testGetUniqueId(): void
    {
        $this->joinPoint->setMethod('testMethod');
        $this->joinPoint->setParams(['param1' => 'value1']);
        $this->joinPoint->setInternalServiceId('test.service');

        $uniqueId = $this->joinPoint->getUniqueId();
        $this->assertNotEmpty($uniqueId);

        // Test that the same inputs produce the same unique ID
        $joinPoint2 = new JoinPoint();
        $joinPoint2->setMethod('testMethod');
        $joinPoint2->setParams(['param1' => 'value1']);
        $joinPoint2->setInternalServiceId('test.service');

        $this->assertSame($uniqueId, $joinPoint2->getUniqueId());
    }

    public function testProceed(): void
    {
        $instance = new class {
            public function testMethod(string $arg): string
            {
                return 'result_' . $arg;
            }
        };

        $this->joinPoint->setInstance($instance);
        $this->joinPoint->setMethod('testMethod');
        $this->joinPoint->setParams(['test']);

        $result = $this->joinPoint->proceed();
        $this->assertEquals('result_test', $result);
    }

    public function testToArray(): void
    {
        $this->joinPoint->setMethod('testMethod');
        $this->joinPoint->setInternalServiceId('test.service');

        $array = $this->joinPoint->toArray();
        $this->assertArrayHasKey('serviceId', $array);
        $this->assertArrayHasKey('method', $array);
        $this->assertEquals('test.service', $array['serviceId']);
        $this->assertEquals('testMethod', $array['method']);
    }
}
