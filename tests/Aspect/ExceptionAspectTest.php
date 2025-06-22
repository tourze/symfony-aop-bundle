<?php

namespace Tourze\Symfony\Aop\Tests\Aspect;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\Aop\Aspect\ExceptionAspect;
use Tourze\Symfony\Aop\Model\JoinPoint;

class ExceptionAspectTest extends TestCase
{
    private ExceptionAspect $aspect;
    
    public function testCatchExceptionWithException(): void
    {
        $exception = new \RuntimeException('Test exception');
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getException')->willReturn($exception);

        // The method should handle the exception without throwing
        $this->aspect->catchException($joinPoint);

        // No assertions needed as the method just handles the exception
        $this->assertTrue(true);
    }
    
    public function testCatchExceptionWithoutException(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getException')->willReturn(null);

        // The method should handle null exception gracefully
        $this->aspect->catchException($joinPoint);

        // No assertions needed as the method handles null case
        $this->assertTrue(true);
    }
    
    public function testCatchExceptionWithDifferentExceptionTypes(): void
    {
        $exceptions = [
            new \Exception('General exception'),
            new \RuntimeException('Runtime exception'),
            new \InvalidArgumentException('Invalid argument'),
            new \LogicException('Logic exception'),
            new \TypeError('Type error'),
        ];

        foreach ($exceptions as $exception) {
            $joinPoint = $this->createMock(JoinPoint::class);
            $joinPoint->method('getException')->willReturn($exception);

            // Each exception type should be handled without throwing
            $this->aspect->catchException($joinPoint);
        }

        // All exceptions handled successfully
        $this->assertTrue(true);
    }
    
    public function testCatchExceptionMultipleCalls(): void
    {
        $joinPoint1 = $this->createMock(JoinPoint::class);
        $joinPoint1->method('getException')->willReturn(new \Exception('Exception 1'));

        $joinPoint2 = $this->createMock(JoinPoint::class);
        $joinPoint2->method('getException')->willReturn(null);

        $joinPoint3 = $this->createMock(JoinPoint::class);
        $joinPoint3->method('getException')->willReturn(new \Exception('Exception 3'));

        // Multiple calls should all be handled properly
        $this->aspect->catchException($joinPoint1);
        $this->aspect->catchException($joinPoint2);
        $this->aspect->catchException($joinPoint3);

        // All calls handled successfully
        $this->assertTrue(true);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->aspect = new ExceptionAspect();
    }
}