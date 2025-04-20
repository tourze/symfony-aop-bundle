<?php

namespace Tourze\Symfony\Aop\Tests\Fixtures;

use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Model\JoinPoint;

/**
 * 使用自定义通知的切面
 */
#[Aspect]
class CustomAdviceAspect
{
    private array $log = [];

    public function getLog(): array
    {
        return $this->log;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    private function addLog(string $message): void
    {
        $this->log[] = $message;
    }

    #[CustomAdvice('class.getName() === "Tourze\\\\Symfony\\\\Aop\\\\Tests\\\\Fixtures\\\\TestService" && method.getName() === "normalMethod"')]
    public function customAdviceMethod(JoinPoint $joinPoint): void
    {
        $params = $joinPoint->getParams();
        $param1 = $params['param1'] ?? 'unknown';
        $param2 = $params['param2'] ?? 0;

        $this->addLog("CustomAdvice: normalMethod with $param1 and $param2");
    }
}
