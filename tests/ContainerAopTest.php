<?php

namespace Tourze\Symfony\Aop\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\DependencyInjection\Compiler\AopAttributeCompilerPass;
use Tourze\Symfony\Aop\Service\AopInterceptor;
use Tourze\Symfony\Aop\Tests\Fixtures\ContainerTestAspect;
use Tourze\Symfony\Aop\Tests\Fixtures\ContainerTestService;
use Tourze\Symfony\Aop\Tests\Fixtures\TestRuntimeException;

/**
 * @internal
 */
#[CoversClass(AopAttributeCompilerPass::class)]
final class ContainerAopTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        // 直接创建编译器传递实例
        $compilerPass = new AopAttributeCompilerPass();

        // 测试 process 方法在没有切面的情况下正常工作
        $compilerPass->process($container);

        // 验证没有切面时，不会添加任何服务定义
        $this->assertFalse($container->hasDefinition('sf-aop.test.service.internal-for-aop'));
    }

    public function testAopWithContainer(): void
    {
        $container = new ContainerBuilder();

        // Set up proxy manager services
        $cacheDir = sys_get_temp_dir() . '/aop-test-' . uniqid();
        @mkdir($cacheDir, 0o777, true);

        $fileLocatorDef = new Definition(FileLocator::class);
        $fileLocatorDef->setArguments([$cacheDir]);
        $container->setDefinition('sf-aop.file-locator', $fileLocatorDef);

        $generatorStrategyDef = new Definition(FileWriterGeneratorStrategy::class);
        $generatorStrategyDef->setArguments([new Reference('sf-aop.file-locator')]);
        $container->setDefinition('sf-aop.generator-strategy', $generatorStrategyDef);

        $configurationDef = new Definition(Configuration::class);
        $configurationDef->addMethodCall('setGeneratorStrategy', [new Reference('sf-aop.generator-strategy')]);
        $configurationDef->addMethodCall('setProxiesTargetDir', [$cacheDir]);
        $configurationDef->addMethodCall('setProxiesNamespace', ['AopProxy']);
        $container->setDefinition('sf-aop.configuration', $configurationDef);

        $proxyManagerDef = new Definition(AccessInterceptorValueHolderFactory::class);
        $proxyManagerDef->setArguments([new Reference('sf-aop.configuration')]);
        $container->setDefinition('sf-aop.value-holder-proxy-manager', $proxyManagerDef);

        // Register AopInterceptor prototype
        $interceptorPrototype = new Definition(AopInterceptor::class);
        $container->setDefinition(AopInterceptor::class, $interceptorPrototype);

        // Register test service
        $serviceDef = new Definition(ContainerTestService::class);
        $serviceDef->setPublic(true);
        $container->setDefinition('test.service', $serviceDef);

        // Register test aspect
        $aspectDef = new Definition(ContainerTestAspect::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $aspectDef->setPublic(true);
        $container->setDefinition('test.aspect', $aspectDef);

        // Add compiler pass
        $compilerPass = new AopAttributeCompilerPass();
        $container->addCompilerPass($compilerPass);

        // Compile container
        $container->compile();

        // Get services
        $service = $container->get('test.service');
        $this->assertInstanceOf(ContainerTestService::class, $service);
        $aspect = $container->get('test.aspect');
        $this->assertInstanceOf(ContainerTestAspect::class, $aspect);

        // Test method interception
        $result = $service->doWork();

        // Verify execution order
        $this->assertEquals(['before', 'afterReturning', 'after'], $aspect->log);

        // Verify result is not modified (AfterReturning doesn't support it)
        $this->assertEquals('original', $result);

        // Test exception handling
        $aspect->log = [];
        try {
            $service->throwError();
            self::fail('Expected exception was not thrown');
        } catch (TestRuntimeException $e) {
            $this->assertEquals('Test error', $e->getMessage());
            $this->assertEquals(['afterThrowing'], $aspect->log);
        }

        // Clean up
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
