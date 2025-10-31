<?php

namespace Tourze\Symfony\Aop\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Symfony\Aop\Exception\AopException;

/**
 * @internal
 */
#[CoversClass(AopException::class)]
final class AopExceptionTest extends AbstractExceptionTestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new AopException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Test error message';
        $exception = new AopException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Test error message';
        $code = 12345;
        $exception = new AopException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }
}
