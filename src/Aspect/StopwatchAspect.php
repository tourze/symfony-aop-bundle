<?php

namespace Tourze\Symfony\AOP\Aspect;

use Symfony\Component\Stopwatch\Stopwatch as SComponent;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\Symfony\AOP\Attribute\After;
use Tourze\Symfony\AOP\Attribute\Aspect;
use Tourze\Symfony\AOP\Attribute\Before;
use Tourze\Symfony\AOP\Attribute\Stopwatch;
use Tourze\Symfony\AOP\Model\JoinPoint;

#[Aspect]
class StopwatchAspect
{
    public function __construct(
        private readonly ?SComponent $stopwatch = null,
    )
    {
        $this->eventMap = new \WeakMap();
    }

    private \WeakMap $eventMap;

    #[Before(methodAttribute: Stopwatch::class)]
    public function startEvent(JoinPoint $joinPoint): void
    {
        if (!$this->stopwatch) {
            return;
        }
        $event = $this->stopwatch->start($joinPoint->getMethod(), $joinPoint->getInternalServiceId());
        $this->eventMap->offsetSet($joinPoint, $event);
    }

    #[After(methodAttribute: Stopwatch::class)]
    public function stopEvent(JoinPoint $joinPoint): void
    {
        if (!$this->stopwatch) {
            return;
        }
        if (!$this->eventMap->offsetExists($joinPoint)) {
            return;
        }
        $event = $this->eventMap->offsetGet($joinPoint);
        /** @var StopwatchEvent $event */
        $event->stop();
    }
}
