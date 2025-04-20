<?php

namespace Tourze\Symfony\Aop\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tourze\Symfony\Aop\DependencyInjection\Compiler\AopAttributeCompilerPass;
use Tourze\Symfony\Aop\Service\AopInterceptor;
use Tourze\Symfony\Aop\Service\InstanceService;
use Tourze\Symfony\Aop\Tests\Fixtures\TestAspect;
use Tourze\Symfony\Aop\Tests\Fixtures\TestService;

class AopTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        // 注册必要的服务
        $this->container->register('sf-aop.value-holder-proxy-manager', InstanceService::class);
        $this->container->register(AopInterceptor::class, AopInterceptor::class);
        $this->container->register(ExpressionLanguage::class, ExpressionLanguage::class);

        // 注册测试服务
        $testServiceDef = new Definition(TestService::class);
        $testServiceDef->setPublic(true);
        $this->container->setDefinition('test.service', $testServiceDef);

        // 注册测试切面
        $testAspectDef = new Definition(TestAspect::class);
        $testAspectDef->setPublic(true);
        $testAspectDef->addTag('aop.aspect');
        $this->container->setDefinition('test.aspect', $testAspectDef);

        // 添加并执行 AOP 编译器
        $this->container->addCompilerPass(new AopAttributeCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $this->container->compile();
    }

    public function testNormalMethodWithAspects(): void
    {
        $this->markTestSkipped('需要在完整的 Symfony 环境中运行');

        /** @var TestService $service */
        $service = $this->container->get('test.service');
        /** @var TestAspect $aspect */
        $aspect = $this->container->get('test.aspect');

        $service->clearLog();
        $aspect->clearLog();

        $result = $service->normalMethod('hello', 123);

        $this->assertEquals('Result: hello-123', $result);

        // 检查服务日志
        $serviceLog = $service->getLog();
        $this->assertCount(1, $serviceLog);
        $this->assertEquals('normalMethod executed with hello and 123', $serviceLog[0]);

        // 检查切面日志
        $aspectLog = $aspect->getLog();
        $this->assertCount(3, $aspectLog);
        $this->assertEquals('Before: normalMethod with hello and 123', $aspectLog[0]);
        $this->assertEquals('AfterReturning: normalMethod returned Result: hello-123', $aspectLog[1]);
        $this->assertEquals('After: normalMethod', $aspectLog[2]);
    }

    public function testExceptionMethodWithAspects(): void
    {
        $this->markTestSkipped('需要在完整的 Symfony 环境中运行');

        /** @var TestService $service */
        $service = $this->container->get('test.service');
        /** @var TestAspect $aspect */
        $aspect = $this->container->get('test.aspect');

        $service->clearLog();
        $aspect->clearLog();

        try {
            $service->exceptionMethod('test error');
            $this->fail('Exception should have been thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('test error', $e->getMessage());
        }

        // 检查服务日志
        $serviceLog = $service->getLog();
        $this->assertCount(1, $serviceLog);
        $this->assertEquals('exceptionMethod about to throw exception with message: test error', $serviceLog[0]);

        // 检查切面日志
        $aspectLog = $aspect->getLog();
        $this->assertCount(3, $aspectLog);
        $this->assertEquals('Before: exceptionMethod with message: test error', $aspectLog[0]);
        $this->assertEquals('AfterThrowing: exceptionMethod threw exception with message: test error', $aspectLog[1]);
        $this->assertEquals('After: exceptionMethod', $aspectLog[2]);
    }

    /**
     * 测试 Before 切面修改传入参数
     */
    public function testBeforeAdviceModifyingParams(): void
    {
        $this->markTestSkipped('需要在完整的 Symfony 环境中运行');

        // 创建一个动态测试切面，它会修改传入参数
        $modifyingAspectDef = new Definition();
        $modifyingAspectDef->setClass(new class extends TestAspect {
            #[\Override]
            public function beforeNormalMethod(\Tourze\Symfony\Aop\Model\JoinPoint $joinPoint): void
            {
                parent::beforeNormalMethod($joinPoint);

                // 修改参数
                $params = $joinPoint->getParams();
                $params['param1'] = 'modified';
                $params['param2'] = 999;
                $joinPoint->setParams($params);
            }
        });
        $modifyingAspectDef->addTag('aop.aspect');
        $this->container->setDefinition('test.modifying_aspect', $modifyingAspectDef);

        /** @var TestService $service */
        $service = $this->container->get('test.service');

        $service->clearLog();

        $result = $service->normalMethod('original', 123);

        $this->assertEquals('Result: modified-999', $result);

        // 检查服务日志
        $serviceLog = $service->getLog();
        $this->assertCount(1, $serviceLog);
        $this->assertEquals('normalMethod executed with modified and 999', $serviceLog[0]);
    }
}
