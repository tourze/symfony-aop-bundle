<?php

namespace Tourze\Symfony\Aop\Tests\Aspect;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\Symfony\Aop\Aspect\StopwatchAspect;
use Tourze\Symfony\Aop\Model\JoinPoint;

class StopwatchAspectTest extends TestCase
{
    public function testStartEventWithStopwatch(): void
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $event = $this->createMock(StopwatchEvent::class);
        
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getMethod')->willReturn('testMethod');
        $joinPoint->method('getInternalServiceId')->willReturn('test.service');
        
        $stopwatch->expects($this->once())
            ->method('start')
            ->with('testMethod', 'test.service')
            ->willReturn($event);
        
        $aspect = new StopwatchAspect($stopwatch);
        $aspect->startEvent($joinPoint);
    }
    
    public function testStartEventWithoutStopwatch(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        
        // Should not throw any exception
        $aspect = new StopwatchAspect(null);
        $aspect->startEvent($joinPoint);
        
        // No assertions needed, just ensure no error occurs
        $this->assertTrue(true);
    }
    
    public function testStopEventWithStartedEvent(): void
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $event = $this->createMock(StopwatchEvent::class);
        
        $joinPoint = $this->createMock(JoinPoint::class);
        $joinPoint->method('getMethod')->willReturn('testMethod');
        $joinPoint->method('getInternalServiceId')->willReturn('test.service');
        
        $stopwatch->expects($this->once())
            ->method('start')
            ->with('testMethod', 'test.service')
            ->willReturn($event);
        
        $event->expects($this->once())
            ->method('stop');
        
        $aspect = new StopwatchAspect($stopwatch);
        $aspect->startEvent($joinPoint);
        $aspect->stopEvent($joinPoint);
    }
    
    public function testStopEventWithoutStartedEvent(): void
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $joinPoint = $this->createMock(JoinPoint::class);
        
        // Should not throw any exception when trying to stop non-existent event
        $aspect = new StopwatchAspect($stopwatch);
        $aspect->stopEvent($joinPoint);
        
        // No assertions needed, just ensure no error occurs
        $this->assertTrue(true);
    }
    
    public function testStopEventWithoutStopwatch(): void
    {
        $joinPoint = $this->createMock(JoinPoint::class);
        
        // Should not throw any exception
        $aspect = new StopwatchAspect(null);
        $aspect->stopEvent($joinPoint);
        
        // No assertions needed, just ensure no error occurs
        $this->assertTrue(true);
    }
    
    public function testMultipleJoinPoints(): void
    {
        $stopwatch = $this->createMock(Stopwatch::class);
        $event1 = $this->createMock(StopwatchEvent::class);
        $event2 = $this->createMock(StopwatchEvent::class);
        
        $joinPoint1 = $this->createMock(JoinPoint::class);
        $joinPoint1->method('getMethod')->willReturn('method1');
        $joinPoint1->method('getInternalServiceId')->willReturn('service1');
        
        $joinPoint2 = $this->createMock(JoinPoint::class);
        $joinPoint2->method('getMethod')->willReturn('method2');
        $joinPoint2->method('getInternalServiceId')->willReturn('service2');
        
        $stopwatch->expects($this->exactly(2))
            ->method('start')
            ->willReturnCallback(function ($method, $category) use ($event1, $event2) {
                return $method === 'method1' ? $event1 : $event2;
            });
        
        $event1->expects($this->once())->method('stop');
        $event2->expects($this->once())->method('stop');
        
        $aspect = new StopwatchAspect($stopwatch);
        
        // Start both events
        $aspect->startEvent($joinPoint1);
        $aspect->startEvent($joinPoint2);
        
        // Stop both events
        $aspect->stopEvent($joinPoint1);
        $aspect->stopEvent($joinPoint2);
    }
}