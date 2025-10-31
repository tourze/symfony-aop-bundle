<?php

namespace Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\DependencyInjection\Compiler\AopAttributeCompilerPass;
use Tourze\Symfony\Aop\Service\AopInterceptor;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AbstractServiceClass;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AspectClass;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AspectClassForInterfaces;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AspectClassForParents;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AspectClassForTags;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\AspectWithMultipleAdvices;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\ServiceFactory;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\ServiceInterface;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\ServiceWithFactoryClass;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\ServiceWithInterface;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\ServiceWithParentClass;
use Tourze\Symfony\Aop\Tests\DependencyInjection\Compiler\Fixtures\TargetService;

/**
 * @internal
 */
#[CoversClass(AopAttributeCompilerPass::class)]
final class AopAttributeCompilerPassTest extends TestCase
{
    /**
     * @return array{0: ContainerBuilder, 1: AopAttributeCompilerPass}
     */
    private function createContainerAndCompilerPass(): array
    {
        $container = new ContainerBuilder();
        // 直接创建编译器传递实例
        $compilerPass = new AopAttributeCompilerPass();

        // 注册 AopInterceptor 原型定义
        $interceptorPrototype = new Definition(AopInterceptor::class);
        $container->setDefinition(AopInterceptor::class, $interceptorPrototype);

        // 注册代理管理器
        $proxyManagerDef = new Definition();
        $proxyManagerDef->setClass('stdClass'); // 模拟的代理管理器
        $container->setDefinition('sf-aop.value-holder-proxy-manager', $proxyManagerDef);

        return [$container, $compilerPass];
    }

    public function testProcessWithNoAspects(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加一个普通服务
        $serviceDef = new Definition(TargetService::class);
        $container->setDefinition('test.service', $serviceDef);

        $compilerPass->process($container);

        // 验证服务未被修改
        $this->assertSame($serviceDef, $container->getDefinition('test.service'));
    }

    public function testProcessWithAspect(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面服务
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加目标服务
        $targetDef = new Definition(TargetService::class);
        $targetDef->addTag('test.tag');
        $container->setDefinition('test.target', $targetDef);

        $compilerPass->process($container);

        // 验证内部服务已创建
        $this->assertTrue($container->hasDefinition('test.target' . AopAttributeCompilerPass::INTERNAL_SUFFIX),
            'Internal service should be created');

        // 验证代理服务已创建
        $proxyDef = $container->getDefinition('test.target');
        $this->assertNotSame($targetDef, $proxyDef);
        $this->assertEquals(TargetService::class, $proxyDef->getClass());

        // 验证工厂方法设置正确
        $factory = $proxyDef->getFactory();
        $this->assertIsArray($factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertEquals('sf-aop.value-holder-proxy-manager', (string) $factory[0]);
        $this->assertEquals('createProxy', $factory[1]);

        // 验证拦截器已创建
        $this->assertTrue($container->hasDefinition(AopAttributeCompilerPass::INTERNAL_PREFIX . 'test.target.interceptor'));
    }

    public function testMultipleAdvicesOnSameMethod(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加有多个通知的切面
        $aspectDef = new Definition(AspectWithMultipleAdvices::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.multi_aspect', $aspectDef);

        // 添加目标服务
        $targetDef = new Definition(TargetService::class);
        $container->setDefinition('test.target', $targetDef);

        $compilerPass->process($container);

        // 验证拦截器配置了多个通知
        $interceptorDef = $container->getDefinition(AopAttributeCompilerPass::INTERNAL_PREFIX . 'test.target.interceptor');

        $methodCalls = $interceptorDef->getMethodCalls();
        $attributeFunctionCalls = array_filter($methodCalls, fn ($call) => 'addAttributeFunction' === $call[0]);

        // 应该有多个 addAttributeFunction 调用
        $this->assertGreaterThan(1, count($attributeFunctionCalls));
    }

    public function testServiceWithFactory(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加工厂服务
        $factoryDef = new Definition(ServiceFactory::class);
        $container->setDefinition('test.factory', $factoryDef);

        // 添加使用工厂的服务
        $serviceDef = new Definition(ServiceWithFactoryClass::class);
        $serviceDef->setFactory([new Reference('test.factory'), 'createService']);
        $serviceDef->setArguments(['arg1', 'arg2']);
        $container->setDefinition('test.service_with_factory', $serviceDef);

        $compilerPass->process($container);

        // 验证拦截器配置了工厂信息
        $interceptorDef = $container->getDefinition(AopAttributeCompilerPass::INTERNAL_PREFIX . 'test.service_with_factory.interceptor');

        $methodCalls = $interceptorDef->getMethodCalls();
        $factoryMethodCalls = array_filter($methodCalls, fn ($call) => str_starts_with($call[0], 'setFactory'));

        $this->assertCount(3, $factoryMethodCalls); // setFactoryInstance, setFactoryMethod, setFactoryArguments
    }

    public function testExpressionMatching(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 创建一个只匹配带特定标签服务的切面
        $aspectDef = new Definition(AspectClassForTags::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加带标签的服务
        $service1Def = new Definition(TargetService::class);
        $service1Def->addTag('monitored');
        $container->setDefinition('test.service1', $service1Def);

        // 添加不带标签的服务
        $service2Def = new Definition(TargetService::class);
        $container->setDefinition('test.service2', $service2Def);

        $compilerPass->process($container);

        // 验证只有带标签的服务被代理
        $this->assertTrue($container->hasDefinition('test.service1' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
        $this->assertFalse($container->hasDefinition('test.service2' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testParentClassMatching(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加匹配父类的切面
        $aspectDef = new Definition(AspectClassForParents::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加继承服务
        $serviceDef = new Definition(ServiceWithParentClass::class);
        $container->setDefinition('test.service_with_parent', $serviceDef);

        $compilerPass->process($container);

        // 验证服务被代理
        $this->assertTrue($container->hasDefinition('test.service_with_parent' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testInterfaceMatching(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加匹配接口的切面
        $aspectDef = new Definition(AspectClassForInterfaces::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加实现接口的服务
        $serviceDef = new Definition(ServiceWithInterface::class);
        $container->setDefinition('test.service_with_interface', $serviceDef);

        $compilerPass->process($container);

        // 验证服务被代理
        $this->assertTrue($container->hasDefinition('test.service_with_interface' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testInternalServiceSkipped(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加内部服务（应该被跳过）
        $internalDef = new Definition(TargetService::class);
        $container->setDefinition(AopAttributeCompilerPass::INTERNAL_PREFIX . 'internal.service', $internalDef);

        // 添加另一个内部服务
        $internal2Def = new Definition(TargetService::class);
        $container->setDefinition('service' . AopAttributeCompilerPass::INTERNAL_SUFFIX, $internal2Def);

        $compilerPass->process($container);

        // 验证内部服务未被处理
        $this->assertSame($internalDef, $container->getDefinition(AopAttributeCompilerPass::INTERNAL_PREFIX . 'internal.service'));
        $this->assertSame($internal2Def, $container->getDefinition('service' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testAspectServiceNotProxied(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面服务
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        $compilerPass->process($container);

        // 验证切面服务本身不会被代理
        $this->assertFalse($container->hasDefinition('test.aspect' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testMagicMethodsSkipped(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加目标服务
        $targetDef = new Definition(TargetService::class);
        $container->setDefinition('test.target', $targetDef);

        $compilerPass->process($container);

        // 验证代理服务的方法调用中不包含魔术方法
        $proxyDef = $container->getDefinition('test.target');
        $methodCalls = $proxyDef->getMethodCalls();

        foreach ($methodCalls as $call) {
            if ('setMethodPrefixInterceptor' === $call[0]) {
                $this->assertNotEquals('__construct', $call[1][0]);
                $this->assertNotEquals('__destruct', $call[1][0]);
                $this->assertNotEquals('__unserialize', $call[1][0]);
            }
        }
    }

    public function testAbstractServiceSkipped(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加抽象服务
        $abstractDef = new Definition(AbstractServiceClass::class);
        $abstractDef->setAbstract(true);
        $container->setDefinition('test.abstract', $abstractDef);

        $compilerPass->process($container);

        // 验证抽象服务未被代理
        $this->assertFalse($container->hasDefinition('test.abstract' . AopAttributeCompilerPass::INTERNAL_SUFFIX));
    }

    public function testTagPreservation(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加带多个标签的服务
        $targetDef = new Definition(TargetService::class);
        $targetDef->addTag('tag1', ['attr' => 'value1']);
        $targetDef->addTag('tag2', ['attr' => 'value2']);
        $container->setDefinition('test.target', $targetDef);

        $compilerPass->process($container);

        // 验证代理服务保留了原始标签
        $proxyDef = $container->getDefinition('test.target');
        $this->assertTrue($proxyDef->hasTag('tag1'));
        $this->assertTrue($proxyDef->hasTag('tag2'));

        // 验证内部服务没有标签
        $internalDef = $container->getDefinition('test.target' . AopAttributeCompilerPass::INTERNAL_SUFFIX);
        $this->assertEmpty($internalDef->getTags());
    }

    public function testPublicServiceHandling(): void
    {
        [$container, $compilerPass] = $this->createContainerAndCompilerPass();

        // 添加切面
        $aspectDef = new Definition(AspectClass::class);
        $aspectDef->addTag(Aspect::TAG_NAME);
        $container->setDefinition('test.aspect', $aspectDef);

        // 添加公共服务
        $publicDef = new Definition(TargetService::class);
        $publicDef->setPublic(true);
        $container->setDefinition('test.public', $publicDef);

        // 添加私有服务
        $privateDef = new Definition(TargetService::class);
        $privateDef->setPublic(false);
        $container->setDefinition('test.private', $privateDef);

        $compilerPass->process($container);

        // 只有匹配的服务会被代理
        if ($container->hasDefinition('test.public' . AopAttributeCompilerPass::INTERNAL_SUFFIX)) {
            // 验证代理服务保持了原始的可见性
            $publicProxyDef = $container->getDefinition('test.public');
            $this->assertTrue($publicProxyDef->isPublic());

            // 验证内部服务是公共的（为了动态调用）
            $publicInternalDef = $container->getDefinition('test.public' . AopAttributeCompilerPass::INTERNAL_SUFFIX);
            $this->assertTrue($publicInternalDef->isPublic());
        }

        if ($container->hasDefinition('test.private' . AopAttributeCompilerPass::INTERNAL_SUFFIX)) {
            $privateProxyDef = $container->getDefinition('test.private');
            $this->assertFalse($privateProxyDef->isPublic());

            $privateInternalDef = $container->getDefinition('test.private' . AopAttributeCompilerPass::INTERNAL_SUFFIX);
            $this->assertTrue($privateInternalDef->isPublic());
        }
    }

    public function testGetParentClasses(): void
    {
        $reflectionClass = new \ReflectionClass(ServiceWithParentClass::class);

        // 使用反射调用私有方法
        $method = new \ReflectionMethod(AopAttributeCompilerPass::class, 'getParentClasses');
        $method->setAccessible(true);

        $result = $method->invoke(null, $reflectionClass);

        // 验证返回了类本身、父类和接口
        $this->assertContains(ServiceWithParentClass::class, $result);
        $this->assertContains(AbstractServiceClass::class, $result);
        $this->assertContains(ServiceInterface::class, $result);
    }
}
