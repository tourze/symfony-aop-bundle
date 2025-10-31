<?php

namespace Tourze\Symfony\Aop\Aspect;

use Symfony\Component\Stopwatch\Stopwatch as SComponent;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\Symfony\Aop\Attribute\After;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Attribute\Before;
use Tourze\Symfony\Aop\Attribute\Stopwatch;
use Tourze\Symfony\Aop\Model\JoinPoint;

#[Aspect]
class StopwatchAspect
{
    public function __construct(
        private readonly ?SComponent $stopwatch = null,
    ) {
        $this->eventMap = new \WeakMap();
    }

    /**
     * @var \WeakMap<JoinPoint, StopwatchEvent>
     */
    private \WeakMap $eventMap;

    #[Before(methodAttribute: Stopwatch::class)]
    public function startEvent(JoinPoint $joinPoint): void
    {
        if (null === $this->stopwatch) {
            return;
        }
        $event = $this->stopwatch->start($joinPoint->getMethod(), $joinPoint->getInternalServiceId());
        $this->eventMap->offsetSet($joinPoint, $event);
    }

    #[After(methodAttribute: Stopwatch::class)]
    public function stopEvent(JoinPoint $joinPoint): void
    {
        if (null === $this->stopwatch) {
            return;
        }
        if (!$this->eventMap->offsetExists($joinPoint)) {
            return;
        }
        $event = $this->eventMap->offsetGet($joinPoint);
        /* @var StopwatchEvent $event */
        $event->stop();
    }
}
