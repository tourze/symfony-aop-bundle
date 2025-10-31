<?php

declare(strict_types=1);

namespace Tourze\Symfony\Aop\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\Symfony\Aop\AopBundle;

/**
 * @internal
 */
#[CoversClass(AopBundle::class)]
#[RunTestsInSeparateProcesses]
final class AopBundleTest extends AbstractBundleTestCase
{
}
