<?php

namespace Tourze\Symfony\Aop\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\Symfony\Aop\AopBundle;
use Tourze\Symfony\Aop\DependencyInjection\Compiler\AopAttributeCompilerPass;

class AopBundleTest extends TestCase
{
    public function testBundleBuild(): void
    {
        $bundle = new AopBundle();
        /** @var ContainerBuilder|MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        // 验证是否添加了正确的编译器
        $containerBuilder->expects($this->once())
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(AopAttributeCompilerPass::class),
                $this->equalTo(PassConfig::TYPE_BEFORE_REMOVING)
            );

        $bundle->build($containerBuilder);
    }
}
