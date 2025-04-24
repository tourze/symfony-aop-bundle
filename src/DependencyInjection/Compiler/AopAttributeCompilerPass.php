<?php

namespace Tourze\Symfony\Aop\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Tourze\Symfony\Aop\Attribute\Advice;
use Tourze\Symfony\Aop\Attribute\Aspect;
use Tourze\Symfony\Aop\Service\AopInterceptor;

class AopAttributeCompilerPass implements CompilerPassInterface
{
    const INTERNAL_PREFIX = 'sf-aop.';
    const INTERNAL_SUFFIX = '.internal-for-aop';

    /**
     * 获取所有可能的类
     */
    protected static function getParentClasses(\ReflectionClass $reflectionClass): array
    {
        $rs = [
            $reflectionClass->getName(),
        ];
        if ($reflectionClass->getParentClass()) {
            $rs = array_merge($rs, static::getParentClasses($reflectionClass->getParentClass()));
        }
        if (!empty($reflectionClass->getInterfaceNames())) {
            $rs = array_merge($rs, $reflectionClass->getInterfaceNames());
        }
        return array_values(array_unique($rs));
    }

    public function process(ContainerBuilder $container): void
    {
        // 收集所有的 Aspect
        // 需要解析其中的所有 Method，判断是否存在切面
        $statements = [];
        foreach (array_keys($container->findTaggedServiceIds(Aspect::TAG_NAME)) as $serviceId) {
            $reflectionClass = $this->getReflectionClass($container, $serviceId);
            if (!$reflectionClass) {
                continue;
            }
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes() as $attribute) {
                    /** @var \ReflectionAttribute $attribute */
                    $name = $attribute->getName();
                    if (is_subclass_of($name, Advice::class)) {
                        $instance = $attribute->newInstance();
                        /** @var Advice $instance */
                        if (!isset($statements[$instance->statement])) {
                            $statements[$instance->statement] = [];
                        }
                        if (!isset($statements[$instance->statement][$name])) {
                            $statements[$instance->statement][$name] = [];
                        }
                        $statements[$instance->statement][$name][] = [$serviceId, $method->getName()];
                        // 强制修改为public，方便Interceptor去动态执行
                        $definition = $container->findDefinition($serviceId);
                        // Aspect的类如果改为Lazy，可能会导致注入逻辑不符合预期
                        // $definition->setLazy(true);
                        $definition->setPublic(true);
                    }
                }
            }
        }

        if (empty($statements)) {
            return;
        }

        // 开始切入
        // 这里为了快速实现，我们使用了表达式组件，跟Spring的实现有差异
        $expressionLanguage = new ExpressionLanguage(null, $container->getExpressionLanguageProviders());
        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('count'));
        foreach ($container->getServiceIds() as $serviceId) {
            if ($this->isInternalService($serviceId)) {
                continue;
            }
            $reflectionClass = $this->getReflectionClass($container, $serviceId);
            if (!$reflectionClass) {
                continue;
            }
            // 为了减少循环依赖问题，这里我们暂时忽略Aspect
            if (!empty($reflectionClass->getAttributes(Aspect::class))) {
                continue;
            }

            $definition = $container->findDefinition($serviceId);
            $serviceTags = array_keys($definition->getTags());

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                // 部分魔术方法，我们不处理
                if ($method->getName() === '__construct' || $method->getName() === '__destruct' || $method->getName() === '__unserialize') {
                    continue;
                }

                $fullName = $reflectionClass->getName() . '::' . $method->getName();
                foreach ($statements as $statement => $config) {
                    //dump($serviceId, $statement);
                    // 满足条件，我们开始拦截方法
                    if (
                        $statement !== $fullName
                        && !$expressionLanguage->evaluate($statement, [
                            'class' => $reflectionClass,
                            'method' => $method,
                            'serviceId' => $serviceId,
                            'serviceTags' => $serviceTags,
                            'parentClasses' => self::getParentClasses($reflectionClass),
                        ])) {
                        continue;
                    }

                    // 创建一个新的service覆盖原service
                    $internalId = $this->updateInternalServiceId($container, $serviceId);
                    $aopNewService = $container->findDefinition($serviceId);

                    // 以 $method 为基准，创建一个 AopInterceptor 对象
                    $interceptorId = self::INTERNAL_PREFIX . "$serviceId.interceptor";
                    $closureId = "{$interceptorId}.closure";
                    if (!$container->hasDefinition($interceptorId)) {
                        $interceptorDefinition = clone $container->getDefinition(AopInterceptor::class);
                        $interceptorDefinition->addMethodCall('setInternalServiceId', [$internalId]);
                        $interceptorDefinition->addMethodCall('setProxyServiceId', [$serviceId]);
                        // 如果原始服务是使用工厂类来生成的，那么我们在连接池也使用工厂类来创建对象
                        $internalDef = $container->getDefinition($internalId);
                        if (is_array($internalDef->getFactory())) {
                            $interceptorDefinition->addMethodCall('setFactoryInstance', [$internalDef->getFactory()[0]]);
                            $interceptorDefinition->addMethodCall('setFactoryMethod', [$internalDef->getFactory()[1]]);
                            $interceptorDefinition->addMethodCall('setFactoryArguments', [$internalDef->getArguments()]);
                        }
                        $interceptorDefinition->setPublic(false); // 这个不应被公开调用
                        $container->setDefinition($interceptorId, $interceptorDefinition);

                        // 直接传一个服务会报错，要Closure才行
                        $closureDefinition = new Definition();
                        $closureDefinition->setClass(\Closure::class);
                        $closureDefinition->setFactory([\Closure::class, 'fromCallable']);
                        $closureDefinition->setArguments([
                            new Reference($interceptorId),
                        ]);
                        $closureDefinition->setPublic(false); // 这个不应被公开调用
                        $container->setDefinition($closureId, $closureDefinition);
                    }

                    $tag = "aop-method:{$method->getName()}";
                    if (empty($aopNewService->getTag($tag))) {
                        $aopNewService->addTag($tag, $config);
                        // 拦截这个方法
                        $aopNewService->addMethodCall('setMethodPrefixInterceptor', [
                            $method->getName(),
                            new Reference($closureId),
                        ]);
                    }

                    $interceptorDefinition = $container->findDefinition($interceptorId);
                    $interceptorDefinition->addTag('aop-intercept', ['method' => $method->getName()]);
                    foreach ($config as $_attribute => $_functions) {
                        foreach ($_functions as [$aspectServiceId, $aspectMethod]) {
                            $interceptorDefinition->addMethodCall('addAttributeFunction', [
                                $method->getName(),
                                $_attribute,
                                new ServiceClosureArgument(new Reference($aspectServiceId)),
                                $aspectMethod,
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function getReflectionClass(ContainerBuilder $container, string $serviceId): ?\ReflectionClass
    {
        // 别名的，我们都跳过
        if ($container->hasAlias($serviceId)) {
            return null;
        }

        $definition = $container->findDefinition($serviceId);
        if (empty($definition->getClass())) {
            return null;
        }
        try {
            if (!class_exists($definition->getClass())) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }
        if ($definition->isAbstract()) {
            return null;
        }

        return $container->getReflectionClass($definition->getClass());
    }

    /**
     * 检查这个服务是否是内置服务，是的话就不要继续处理了
     */
    private function isInternalService(string $serviceId): bool
    {
        if (str_starts_with($serviceId, self::INTERNAL_PREFIX)) {
            return true;
        }
        return str_ends_with($serviceId, self::INTERNAL_SUFFIX);
    }

    /**
     * 隐藏旧服务，并返回隐藏后的服务ID
     */
    private function updateInternalServiceId(ContainerBuilder $container, string $serviceId): string
    {
        $internalId = $serviceId . AopAttributeCompilerPass::INTERNAL_SUFFIX;

        if (!$container->hasDefinition($internalId)) {
            $definition = $container->getDefinition($serviceId);
            if (!$definition->isAbstract() && $serviceId !== 'session.abstract_handler') {
                // 声明为public，方便后面动态调用，目前主要在 ServiceCallHandler 中调用
                $definition->setPublic(true);
            }
            // 我们已经做了一层代理，如果再加上Lazy，感觉调用栈会太过复杂
            // 暂时改为强制lazy
            $definition->setLazy(false);
            // 之所以要清空tags，是因为我们要把这些tag归到代理对象去
            $existTags = $definition->getTags();
            $definition->clearTags();
            // 重新写入，隐藏原始服务
            $container->setDefinition($internalId, $definition);

            $aopNewService = (new Definition($definition->getClass()))
                ->setFactory([new Reference('sf-aop.value-holder-proxy-manager'), 'createProxy'])
                ->setArguments([
                    new Reference($internalId),
                ])
                ->setPublic($definition->isPublic())
                ->setTags($existTags) // 这里重新补上原始服务的tag
                //->addTag('aop-proxy')
                ;
            // 覆盖旧的服务名
            $container->setDefinition($serviceId, $aopNewService);
        }

        return $internalId;
    }
}
