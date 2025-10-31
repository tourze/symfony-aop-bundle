<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\Symfony\Aop\DependencyInjection\AopExtension;

/**
 * @internal
 */
#[CoversClass(AopExtension::class)]
final class AopExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoad(): void
    {
        $configs = [];
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new AopExtension();

        $extension->load($configs, $container);

        // 验证关键服务已注册
        $this->assertTrue($container->hasDefinition('sf-aop.value-holder-proxy-manager'));
    }

    public function testLoadDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new AopExtension();
        $extension->load([], $container);
    }
}
